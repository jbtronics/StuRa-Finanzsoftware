<?php
/*
 * Copyright (C) 2020  Jan Böhmer
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Controller;

use App\Audit\UserProvider;
use App\Entity\PaymentOrder;
use App\Event\PaymentOrderSubmittedEvent;
use App\Form\PaymentOrderConfirmationType;
use App\Form\PaymentOrderType;
use App\Services\PaymentReferenceGenerator;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * This controller handles the payment order submit form.
 *
 * @Route("/payment_order")
 */
class PaymentOrderController extends AbstractController
{
    private $userProvider;

    public function __construct(UserProvider $userProvider)
    {
        $this->userProvider = $userProvider;
    }

    /**
     * @Route("/new", name="payment_order_new")
     */
    public function new(Request $request, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher,
        PaymentReferenceGenerator $paymentReferenceGenerator): Response
    {
        $new_order = new PaymentOrder();

        $form = $this->createForm(PaymentOrderType::class, $new_order);

        if (!$form instanceof Form) {
            throw new InvalidArgumentException('$form must be a Form object!');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->persist($new_order);

                $username = sprintf('%s %s (%s) [New PaymentOrder]',
                    $new_order->getFirstName(),
                    $new_order->getLastName(),
                    $new_order->getContactEmail()
                );
                $this->userProvider->setManualUsername($username, $new_order->getContactEmail());

                $entityManager->flush();


                //We have to do this after the first flush, as we need to know the ID
                $this->userProvider->setManualUsername('Automatic payment reference generation', UserProvider::INTERNAL_USER_IDENTIFIER);
                $paymentReferenceGenerator->setPaymentReference($new_order);
                $entityManager->flush();

                $this->addFlash('success', 'flash.saved_successfully');

                //Dispatch event so an email can be sent
                $event = new PaymentOrderSubmittedEvent($new_order);
                $dispatcher->dispatch($event, $event::NAME);

                //Redirect to homepage, if no further paymentOrders should be submitted
                //Otherwise create a new form for further ones
                if ('submit' === $form->getClickedButton()->getName()) {
                    return $this->redirectToRoute('homepage');
                }

                if ('submit_new' === $form->getClickedButton()->getName()) {
                    $old_order = $new_order;
                    $new_order = new PaymentOrder();
                    $this->copyProperties($old_order, $new_order);

                    $form = $this->createForm(PaymentOrderType::class, $new_order);
                }
            } else {
                $this->addFlash('error', 'flash.error.check_input');
            }
        }

        return $this->render('PaymentOrder/form.html.twig', [
            'form' => $form->createView(),
            'entity' => $new_order,
        ]);
    }

    private function copyProperties(PaymentOrder $source, PaymentOrder $target): void
    {
        $target->setFirstName($source->getFirstName());
        $target->setLastName($source->getLastName());
        $target->setContactEmail($source->getContactEmail());
        $target->setDepartment($source->getDepartment());
        $target->setBankInfo($source->getBankInfo());
    }

    /**
     * @Route("/{id}/confirm", name="payment_order_confirm")
     */
    public function confirmation(PaymentOrder $paymentOrder, Request $request, EntityManagerInterface $em): Response
    {
        //Check if we have one of the valid confirm numbers
        $confirm_step = $request->query->getInt('confirm');
        if (1 !== $confirm_step && 2 !== $confirm_step) {
            //$this->createNotFoundException('Invalid confirmation step! Only 1 or 2 are allowed.');
            $this->addFlash('error', 'payment_order.confirmation.invalid_step');

            return $this->redirectToRoute('homepage');
        }

        //Check if given token is correct for this step
        $correct_token = (1 === $confirm_step) ? $paymentOrder->getConfirm1Token() : $paymentOrder->getConfirm2Token();
        if (null === $correct_token) {
            throw new RuntimeException('This payment_order can not be confirmed! No token is set.');
        }

        $given_token = (string) $request->query->get('token');
        if (!password_verify($given_token, $correct_token)) {
            $this->addFlash('error', 'payment_order.confirmation.invalid_token');

            return $this->redirectToRoute('homepage');
        }

        //Check if it was already confirmed from this side and disable form if needed
        $confirm_timestamp = (1 === $confirm_step) ? $paymentOrder->getConfirm1Timestamp() : $paymentOrder->getConfirm2Timestamp();
        if (null !== $confirm_timestamp) {
            $this->addFlash('info', 'payment_order.confirmation.already_confirmed');
        }
        $form = $this->createForm(PaymentOrderConfirmationType::class, null, [
            'disabled' => null !== $confirm_timestamp,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', 'payment_order.confirmation.success');
            //Write confirmation to DB
            if (1 === $confirm_step) {
                $paymentOrder->setConfirm1Timestamp(new DateTime());
            } elseif (2 === $confirm_step) {
                $paymentOrder->setConfirm2Timestamp(new DateTime());
            }

            //Add hintful information about who did this, to audit log
            $emails = (1 === $confirm_step) ? $paymentOrder->getDepartment()->getEmailHhv() : $paymentOrder->getDepartment()->getEmailTreasurer();
            $username = sprintf('%s [Confirmation %d]', implode(', ', $emails), $confirm_step);
            $this->userProvider->setManualUsername($username, implode(',', $emails));
            $em->flush();

            //Rerender form if it was confirmed, to apply the disabled state
            $form = $this->createForm(PaymentOrderConfirmationType::class, null, [
                'disabled' => true,
            ]);
            $this->addFlash('info', 'payment_order.confirmation.already_confirmed');
        }

        return $this->render('PaymentOrder/confirm/confirm.html.twig', [
            'entity' => $paymentOrder,
            'confirmation_nr' => $confirm_step,
            'form' => $form->createView(),
        ]);
    }
}
