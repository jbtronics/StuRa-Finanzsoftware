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

use App\Entity\BankAccount;
use App\Entity\PaymentOrder;
use App\Exception\SEPAExportAutoModeNotPossible;
use App\Form\SepaExportType;
use App\Helpers\ZIPBinaryFileResponseFacade;
use App\Services\PaymentOrdersSEPAExporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @see \App\Tests\Controller\ExportControllerTest
 */
#[Route(path: '/admin/payment_order')]
final class ExportController extends AbstractController
{
    public function __construct(
        private readonly PaymentOrdersSEPAExporter $sepaExporter,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator)
    {
    }

    #[Route(path: '/export', name: 'payment_order_export')]
    public function export(Request $request, EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted('ROLE_EXPORT_PAYMENT_ORDERS');

        $ids = $request->query->get('ids');
        $id_array = explode(',', $ids);
        //Retrieve all payment orders which should be retrieved from DB:
        $payment_orders = [];
        foreach ($id_array as $id) {
            $payment_orders[] = $entityManager->find(PaymentOrder::class, $id);
        }

        $form = $this->createForm(SepaExportType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //Determine the Values to use
            $data = $form->getData();
            //If user has selected a bank account preset, then use the data from it
            if ($data['bank_account'] instanceof BankAccount) {
                $iban = $data['bank_account']->getIban();
                $bic = $data['bank_account']->getBic();
                $name = $data['bank_account']->getExportAccountName();
            } else { //Use the manual inputted data
                $iban = $data['iban'];
                $bic = $data['bic'];
                $name = $data['name'];
            }

            try {
                $xml_files = $this->sepaExporter->export(
                    $payment_orders,
                    [
                        'iban' => $iban,
                        'bic' => $bic,
                        'name' => $name,
                        'mode' => $data['mode'],
                    ]
                );

                $response = null;

                //Download as file
                if (1 === count($xml_files)) {
                    $xml_string = array_values($xml_files)[0];
                    $filename = 'export_'.date('Y-m-d_H-i-s').'.xml';
                    $response = $this->getDownloadResponse($xml_string, $filename);
                } else {
                    $data = [];
                    foreach ($xml_files as $key => $content) {
                        $data[$key.'.xml'] = $content;
                    }

                    //Dont return already here... We need to set the exported flags first
                    $response = ZIPBinaryFileResponseFacade::createZIPResponseFromData(
                        $data,
                        'export_'.date('Y-m-d_H-i-s').'.zip'
                    );
                }

                //Set export flag
                foreach ($payment_orders as $paymentOrder) {
                    $paymentOrder->setExported(true);
                }
                $this->entityManager->flush();

                return $response;
            } catch (SEPAExportAutoModeNotPossible $exception) {
                //Show error if auto mode is not possible
                $this->addFlash('danger',
                    $this->translator->trans('sepa_export.error.department_missing_account')
                    .': '.$exception->getWrongDepartment()->getName());
            }
        }

        return $this->render('admin/payment_order/export/export.html.twig', [
            'payment_orders' => $payment_orders,
            'form' => $form->createView(),
        ]);
    }

    protected function getDownloadResponse(string $content, string $filename, string $mime_type = 'application/xml'): Response
    {
        $response = new Response();
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', $mime_type);
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'";');
        $response->headers->set('Content-length', strlen($content));
        $response->setContent($content);

        return $response;
    }
}
