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

namespace App\Services;


use App\Entity\PaymentOrder;
use Digitick\Sepa\DomBuilder\DomBuilderFactory;
use Digitick\Sepa\GroupHeader;
use Digitick\Sepa\PaymentInformation;
use Digitick\Sepa\TransferFile\CustomerCreditTransferFile;
use Digitick\Sepa\TransferFile\Factory\TransferFileFacadeFactory;
use Digitick\Sepa\TransferInformation\CustomerCreditTransferInformation;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\UnicodeString;

class PaymentOrdersSEPAExporter
{
    protected const PARTY_NAME = "StuRa FSU Jena";
    protected const ID_PREFIX = "StuRa Export";
    protected const PAYMENT_PREFIX = "Payment";

    public function export(array $payment_orders, array $options): string
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $groupHeader = new GroupHeader(
            static::ID_PREFIX . ' ' . uniqid('', false),
            static::PARTY_NAME
        );
        $sepaFile = new CustomerCreditTransferFile($groupHeader);

        $payment = new PaymentInformation(static::PAYMENT_PREFIX . ' ' . uniqid('', false),
                                          $options['iban'],
                                          $options['bic'],
                                          $options['name']
        );

        //We only have one SEPA-Payment but it contains multiple transactions (each for one PaymentOrder)
        foreach($payment_orders as $payment_order) {
            /** @var PaymentOrder $payment_order */

            $transfer = new CustomerCreditTransferInformation(
                $payment_order->getAmountString(),
                //We need a IBAN without spaces
                str_replace(' ', '',$payment_order->getBankInfo()->getIban()),
                $payment_order->getBankInfo()->getAccountOwner()
            );
            $transfer->setBic($payment_order->getBankInfo()->getBic());
            $transfer->setRemittanceInformation($payment_order->getBankInfo()->getReference());

            $payment->addTransfer($transfer);
        }

        $sepaFile->addPaymentInformation($payment);

        // Or if you want to use the format 'pain.001.001.03' instead
        $domBuilder = DomBuilderFactory::createDomBuilder($sepaFile, 'pain.001.001.03');

        return $domBuilder->asXml();
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
                                   'iban', //The IBAN of the sender
                                   'bic', //The BIC of the sender
                                   'name', //The name of the sender
                               ]);

        $resolver->setAllowedTypes('iban', 'string');
        $resolver->setAllowedTypes('bic', 'string');
        $resolver->setAllowedTypes('name', 'string');

        $resolver->setNormalizer('iban', function(Options  $options, $value) {
            //Return spaces from IBAN
            return str_replace(' ', '', $value);
        });
    }
}