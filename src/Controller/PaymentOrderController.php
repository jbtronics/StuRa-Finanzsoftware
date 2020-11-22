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


use App\Entity\PaymentOrder;
use App\Entity\User;
use App\Event\PaymentOrderSubmittedEvent;
use App\Form\PaymentOrderConfirmationType;
use App\Form\PaymentOrderType;
use App\Services\PDF\PaymentOrderPDFGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * This controller handles the payment order submit form
 * @Route("/payment_order")
 * @package App\Controller
 */
class PaymentOrderController extends AbstractController
{

    /**
     * @Route("/new", name="payment_order_new")
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher): Response
    {
        $new_order = new PaymentOrder();

        $form = $this->createForm(PaymentOrderType::class, $new_order);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $entityManager->persist($new_order);
                $entityManager->flush();

                $this->addFlash('success', 'flash.saved_successfully');

                //Dispatch event so an email can be sent
                $event = new PaymentOrderSubmittedEvent($new_order);
                $dispatcher->dispatch($event, $event::NAME);

                //Redirect to homepage
                return $this->redirectToRoute('homepage');
            } else {
                $this->addFlash('error', 'flash.error.check_input');
            }
        }

        return $this->render('PaymentOrder/form.html.twig', [
            'form' => $form->createView(),
            'entity' => $new_order,
        ]);
    }

    /**
     * @Route("/{id}/confirm", name="payment_order_confirm")
     * @return Response
     */
    public function confirmation(PaymentOrder $paymentOrder, Request $request, EntityManagerInterface $em): Response
    {
        //Check if we have one of the valid confirm numbers
        $confirm_step = $request->query->getInt('confirm', 0);
        if ($confirm_step !== 1 && $confirm_step !== 2) {
            //$this->createNotFoundException('Invalid confirmation step! Only 1 or 2 are allowed.');
            $this->addFlash('error', 'payment_order.confirmation.invalid_step');
            return $this->redirectToRoute('homepage');
        }


        //Check if given token is correct for this step
        $correct_token = $confirm_step === 1 ? $paymentOrder->getConfirm1Token() : $paymentOrder->getConfirm2Token();
        if ($correct_token === null) {
            throw new \RuntimeException("This payment_order can not be confirmed! No token is set.");
        }

        $given_token = (string) $request->query->get('token');
        if(!password_verify($given_token, $correct_token)) {
            $this->addFlash('error', 'payment_order.confirmation.invalid_token');
            return $this->redirectToRoute('homepage');
        }

        //Check if it was already confirmed from this side and disable form if needed
        $confirm_timestamp = $confirm_step === 1 ? $paymentOrder->getConfirm1Timestamp() : $paymentOrder->getConfirm2Timestamp();
        if($confirm_timestamp !== null) {
            $this->addFlash('info', 'payment_order.confirmation.already_confirmed');
        }
        $form = $this->createForm(PaymentOrderConfirmationType::class, null, [
            'disabled' => $confirm_timestamp !== null
        ]);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $this->addFlash('success', 'payment_order.confirmation.success');
            //Write confirmation to DB
            if($confirm_step === 1) {
                $paymentOrder->setConfirm1Timestamp(new \DateTime());
            } elseif ($confirm_step === 2) {
                $paymentOrder->setConfirm2Timestamp(new \DateTime());
            }
            $em->flush();
        }

        return $this->render('PaymentOrder/confirm/confirm.html.twig', [
            'entity' => $paymentOrder,
            'confirmation_nr' => $confirm_step,
            'form' => $form->createView()
        ]);
    }
}