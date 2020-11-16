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
use App\Event\PaymentOrderSubmittedEvent;
use App\Form\PaymentOrderType;
use App\Services\PDF\PaymentOrderPDFGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
}