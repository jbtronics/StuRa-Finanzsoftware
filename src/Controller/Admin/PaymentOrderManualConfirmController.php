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

namespace App\Controller\Admin;

use App\Entity\PaymentOrder;
use App\Form\PaymentOrderManualConfirmationType;
use App\Services\EmailConfirmation\ManualConfirmationHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/payment_order')]
final class PaymentOrderManualConfirmController extends AbstractController
{
    #[Route(path: '/{id}/confirm', name: 'payment_order_manual_confirm')]
    public function manualConfirmation(PaymentOrder $paymentOrder, Request $request,
        ManualConfirmationHelper $manualConfirmationHelper, EntityManagerInterface $entityManager,
        array $notifications_risky): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANUAL_CONFIRMATION');

        //We can only confirm PaymentOrders that are not confirmed yet
        if ($paymentOrder->isConfirmed()) {
            $this->addFlash('error', 'payment_order.manual_confirm.already_confirmed');

            return $this->redirectToRoute('admin_dashboard');
        }

        $form = $this->createForm(PaymentOrderManualConfirmationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manualConfirmationHelper->confirmManually($paymentOrder, $form->get('reason')->getData());
            //Save changes
            $entityManager->flush();

            //Show a success flash notification
            $this->addFlash('success', 'payment_order.manual_confirm.success');

            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/payment_order/manual_confirm.html.twig', [
            'entity' => $paymentOrder,
            'notifications_risky' => array_filter($notifications_risky),
            'form' => $form->createView(),
        ]);
    }
}
