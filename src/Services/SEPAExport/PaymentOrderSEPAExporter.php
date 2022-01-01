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

namespace App\Services\SEPAExport;

use App\Entity\BankAccount;
use App\Entity\PaymentOrder;
use App\Entity\SEPAExport;
use App\Exception\SEPAExportAutoModeNotPossible;
use App\Helpers\SEPAXML\SEPAXMLExportResult;
use Digitick\Sepa\DomBuilder\BaseDomBuilder;
use Digitick\Sepa\DomBuilder\DomBuilderFactory;
use Digitick\Sepa\PaymentInformation;
use Digitick\Sepa\TransferFile\CustomerCreditTransferFile;
use Digitick\Sepa\TransferInformation\CustomerCreditTransferInformation;
use Webmozart\Assert\Assert;

final class PaymentOrderSEPAExporter
{
    /** @var GroupHeaderHelper */
    private $group_header_helper;

    public function __construct(GroupHeaderHelper $group_header_helper, SEPAExportGroupAndSplitHelper $splitHelper)
    {
        $this->group_header_helper = $group_header_helper;
    }

    /**
     * Exports the given payment orders and automatically assign the used bank accounts.
     * For this the bankaccount of the associated departments are used, and the FSR-Kom bank account if a payment is an
     * FSR-Kom transaction.
     * @param  PaymentOrder[]  $payment_orders The payment orders which should be exported
     * @throws SEPAExportAutoModeNotPossible If an element has no assigned default bank account, then automatic mode is not possible
     * @return SEPAXMLExportResult
     */
    public function exportAuto(array $payment_orders): SEPAXMLExportResult
    {
        //First we have to group the payment orders according to their bank accounts



        return new SEPAXMLExportResult();
    }


    public function exportUsingGivenIBAN(array $payment_orders, string $debtor_iban, string $debtor_bic, string $debtor_account_name): SEPAExport
    {
        //We need IBAN without spaces
        $debtor_iban = str_replace(" ", "", $debtor_iban);

        $groupHeader = $this->group_header_helper->getGroupHeader($debtor_bic);
        $sepaFile = new CustomerCreditTransferFile($groupHeader);

        $payment = new PaymentInformation(
            md5(random_bytes(50)), //Use a random payment identifier ID
            $debtor_iban,
            $debtor_bic,
            $debtor_account_name
        );

        //Disable batch booking, as the commerzbank does not show details for "Sammelüberweisungen"
        $payment->setBatchBooking(false);

        //Add each payment order as transaction
        foreach ($payment_orders as $payment_order) {
            //Ensure that type is correct
            if (!$payment_order instanceof PaymentOrder) {
                throw new \InvalidArgumentException('$payment_orders must be an array of PaymentOrder elements!');
            }

            //Ensure that the ID is available
            if (!$payment_order->getId() === null) {
                throw new \InvalidArgumentException('A payment order that should be exported misses an ID. All payment orders must have been persisted!');
            }

            $transfer = new CustomerCreditTransferInformation(
                $payment_order->getAmount(),
                $payment_order->getBankInfo()->getIbanWithoutSpaces(),
                $payment_order->getBankInfo()->getAccountOwner()
            );

            //BIC is optional, only set it if it was set.
            if (!empty($payment_order->getBankInfo()->getBic())) {
                $transfer->setBic($payment_order->getBankInfo()->getBic());
            }

            //We use the ID String of the payment order as end to end reference
            $transfer->setEndToEndIdentification($payment_order->getIDString());
            //Set the reference ID of the payment order as
            $transfer->setRemittanceInformation($payment_order->getBankInfo()->getReference());

            $payment->addTransfer($transfer);
        }

        //Add payment infos to SEPA file
        $sepaFile->addPaymentInformation($payment);

        // We have to use the format 'pain.001.001.03'
        $domBuilder = DomBuilderFactory::createDomBuilder($sepaFile, 'pain.001.001.03');

        if (!$domBuilder instanceof BaseDomBuilder) {
            throw new \RuntimeException('$domBuilder must be an BaseDomBuilder instance!');
        }

        //Create a temporary file with the XML content
        $xml_string = $domBuilder->asXml();

        //We use the format YYYYMMDDHHmmss_MsgID.xml
        $original_filename = sprintf("%s_%s.xml",
            (new \DateTime())->format('YmdHis'),
            $groupHeader->getMessageIdentification(),
        );

        return SEPAExport::createFromXMLString($xml_string, $original_filename);
    }




}