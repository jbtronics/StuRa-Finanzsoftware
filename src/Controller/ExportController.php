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
use App\Helpers\SEPAXML\SEPAXMLExportResult;
use App\Helpers\ZIPBinaryFileResponseFacade;
use App\Services\PaymentOrdersSEPAExporter_old;
use App\Services\SEPAExport\PaymentOrderSEPAExporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/admin/payment_order")
 */
class ExportController extends AbstractController
{
    protected $sepaExporter;
    protected $translator;
    protected $entityManager;

    public function __construct(PaymentOrderSEPAExporter $sepaExporter, EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->sepaExporter = $sepaExporter;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/export", name="payment_order_export")
     */
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
                //Call function depending on the selected mode
                switch($data['mode']) {
                    case 'auto':
                            $result = $this->sepaExporter->exportAuto($payment_orders);
                        break;
                    case 'auto_single':
                            $result = $this->sepaExporter->exportAutoSingle($payment_orders);
                        break;
                    case 'manual':
                            $result = new SEPAXMLExportResult(
                                [
                                    $this->sepaExporter->exportUsingGivenIBAN($payment_orders, $iban, $bic, $name)
                                ]
                            );
                        break;
                    default:
                        throw new \RuntimeException('Unknown mode!');
                }

                //Set exported flag for each payment order
                foreach ($payment_orders as $paymentOrder) {
                    $paymentOrder->setExported(true);
                }

                //Persist the SEPA exports to database
                $result->persistSEPAExports($this->entityManager);

                $this->entityManager->flush();

                //Return the download
                return $result->getDownloadResponse('export_'.date('Y-m-d_H-i-s'));

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
}
