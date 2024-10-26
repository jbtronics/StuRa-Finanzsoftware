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

use App\Entity\ConfirmationToken;
use App\Entity\PaymentOrder;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Vich\UploaderBundle\Handler\DownloadHandler;

/**
 * @see \App\Tests\Controller\FileContollerTest
 */
#[Route(path: '/file')]
final class FileController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {

    }

    #[Route(path: '/payment_order/{id}/form', name: 'file_payment_order_form')]
    public function paymentOrderForm(PaymentOrder $paymentOrder, DownloadHandler $downloadHandler, Request $request): Response
    {
        $this->checkPermission($paymentOrder, $request);

        if (null === $paymentOrder->getPrintedFormFile()) {
            throw new RuntimeException('The passed paymentOrder does not have an associated form file!');
        }

        return $downloadHandler->downloadObject(
            $paymentOrder,
            'printed_form_file',
            null,
            $paymentOrder->getPrintedFormFile()
                ->getFilename(),
            false
        );
    }

    #[Route(path: '/payment_order/{id}/references', name: 'file_payment_order_references')]
    public function paymentOrderReferences(PaymentOrder $paymentOrder, DownloadHandler $downloadHandler, Request $request): Response
    {
        $this->checkPermission($paymentOrder, $request);

        if (null === $paymentOrder->getReferencesFile()) {
            throw new RuntimeException('The passed paymentOrder does not have an associated references file!');
        }

        return $downloadHandler->downloadObject(
            $paymentOrder,
            'references_file',
            null,
            $paymentOrder->getReferencesFile()
                ->getFilename(),
            false
        );
    }

    private function checkPermission(PaymentOrder $paymentOrder, Request $request): void
    {
        //Check if a valid confirmation token was given, then give access without proper role
        if ($request->query->has('token') && $request->query->has('secret')) {
            //Try to retrieve the token from DB
            $token = $this->entityManager->find(ConfirmationToken::class , $request->query->get('token'));
            if ($token === null) {
                goto role_check;
            }

            //Check if the token is really for the payment order
            if ($token->getPaymentOrder() !== $paymentOrder) {
                goto role_check;
            }

            //Check if the secret is correct
            $secret_hash = $token->getHashedToken();

            $given_secret = (string) $request->query->get('secret');
            if (password_verify($given_secret, $secret_hash)) {
                //If password is correct, skip role checking.
                return;
            }
        }

        role_check:
            //If we dont return anywhere before, we has to check the user roles
            $this->denyAccessUnlessGranted('ROLE_SHOW_PAYMENT_ORDERS');
    }
}
