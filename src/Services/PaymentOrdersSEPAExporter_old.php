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

use App\Entity\BankAccount;
use App\Entity\PaymentOrder;
use App\Exception\SEPAExportAutoModeNotPossible;
use Digitick\Sepa\DomBuilder\BaseDomBuilder;
use Digitick\Sepa\DomBuilder\DomBuilderFactory;
use Digitick\Sepa\GroupHeader;
use Digitick\Sepa\PaymentInformation;
use Digitick\Sepa\TransferFile\CustomerCreditTransferFile;
use Digitick\Sepa\TransferInformation\CustomerCreditTransferInformation;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This service allows to create a SEPA-XML file from a payment order that can be used to import it in an online
 * banking system.
 */
class PaymentOrdersSEPAExporter_old
{
    protected const PARTY_NAME = 'StuRa FSU Jena';
    protected const ID_PREFIX = 'StuRa Export';
    protected const PAYMENT_PREFIX = 'Payment';

    protected $fsr_kom_bank_account_id;
    protected $entityManager;

    public function __construct(int $fsr_kom_bank_account_id, EntityManagerInterface $entityManager)
    {
        $this->fsr_kom_bank_account_id = $fsr_kom_bank_account_id;
        $this->entityManager = $entityManager;
    }

    /**
     * Exports the given paymentOrders as SEPA-XML files.
     *
     * @throws \Digitick\Sepa\Exception\InvalidArgumentException
     */
    public function export_to_array(array $payment_orders, array $options): array
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $accounts = [];
        $return = [];

        if ('manual' === $options['mode']) {
            $accounts[0] = [
                'iban' => $options['iban'],
                'bic' => $options['bic'],
                'name' => $options['name'],
                'entries' => $payment_orders,
            ];
        } elseif ('auto' === $options['mode']) {
            $accounts = $this->groupByBankAccounts($payment_orders);
        } elseif ('auto_single' === $options['mode']) {
            //Export every payment order separately and return early
            foreach ($payment_orders as $payment_order) {
                $return[$payment_order->getIDString()] = $this->exportSinglePaymentOrder($payment_order);
            }

            return $return;
        } else {
            throw new RuntimeException('Unknown mode');
        }

        foreach ($accounts as $account_info) {
            $groupHeader = $this->getGroupHeader();
            $sepaFile = new CustomerCreditTransferFile($groupHeader);

            // A single payment info where all PaymentOrders are added as transactions
            $payment = new PaymentInformation(
                static::PAYMENT_PREFIX.' '.uniqid('', false),
                $account_info['iban'],
                $account_info['bic'],
                $account_info['name']
            );

            $this->addPaymentOrderTransactions($payment, $account_info['entries']);
            $payment->setBatchBooking(false);
            $sepaFile->addPaymentInformation($payment);

            // Or if you want to use the format 'pain.001.001.03' instead
            $domBuilder = DomBuilderFactory::createDomBuilder($sepaFile, 'pain.001.001.03');

            if (!$domBuilder instanceof BaseDomBuilder) {
                throw new InvalidArgumentException('$domBuilder must be an BaseDomBuilder instance!');
            }

            $return[$account_info['name']] = $domBuilder->asXml();
        }

        return $return;
    }

    /**
     * Generates the SEPA-XML file from a single PaymentOrder.
     *
     * @param PaymentOrder $paymentOrder The payment order that should be exported
     * @param string|null  $account_name The name of the debitor account that should be used in export. Set to null to determine automatically
     * @param string|null  $iban         The IBAN of the debitor account that should be used in export. Set to null to determine automatically
     * @param string|null  $bic          The BIC of the debitor account that should be used in export. Set to null to determine automatically
     */
    public function exportSinglePaymentOrder(PaymentOrder $paymentOrder, ?string $account_name = null, ?string $iban = null, ?string $bic = null): string
    {
        //If null values were passed determine them from default bank account
        if (null === $account_name || null === $iban || null === $bic) {
            $bank_account = $this->getResolvedBankAccount($paymentOrder);

            $account_name = $bank_account->getExportAccountName();
            $iban = $bank_account->getIban();
            $bic = $bank_account->getBic();
        } elseif (!($account_name && $iban && $bic)) {
            throw new RuntimeException('You have to pass $account_name, $iban and $bic if you want manually select a debitor account!');
        }

        //Strip spaces from IBAN or we will run into problems
        $iban = str_replace(' ', '', $iban);

        $groupHeader = $this->getGroupHeader();
        $sepaFile = new CustomerCreditTransferFile($groupHeader);

        // A single payment info where all PaymentOrders are added as transactions
        $payment = new PaymentInformation(
            $paymentOrder->getIDString().' '.uniqid('', false),
            $iban,
            $bic,
            $account_name
        );

        $this->addPaymentOrderTransactions($payment, [$paymentOrder]);
        //This line is important
        $payment->setBatchBooking(false);
        $sepaFile->addPaymentInformation($payment);

        // Or if you want to use the format 'pain.001.001.03' instead
        $domBuilder = DomBuilderFactory::createDomBuilder($sepaFile, 'pain.001.001.03');

        if (!$domBuilder instanceof BaseDomBuilder) {
            throw new InvalidArgumentException('$domBuilder must be an BaseDomBuilder instance!');
        }

        return $domBuilder->asXml();
    }

    /**
     * Generates a group header for a SEPA file.
     */
    protected function getGroupHeader(): GroupHeader
    {
        return new GroupHeader(
            static::ID_PREFIX.' '.uniqid('', false),
            static::PARTY_NAME
        );
    }

    /**
     * Generates a payment info ID for the given PaymentOrder.
     * Please not that the results change between calls, as the ID contains a random part to be unique.
     */
    protected function getPaymentInfoID(PaymentOrder $paymentOrder): string
    {
        return $paymentOrder->getIDString().' '.uniqid('', false);
    }

    /**
     * Add the given PaymentOrders as transactions to the SEPA PaymentInformation group.
     *
     * @param PaymentOrder[] $payment_orders
     */
    protected function addPaymentOrderTransactions(PaymentInformation $payment, array $payment_orders): void
    {
        //We only have one SEPA-Payment but it contains multiple transactions (each for one PaymentOrder)
        foreach ($payment_orders as $payment_order) {
            /** @var PaymentOrder $payment_order */

            $transfer = new CustomerCreditTransferInformation(
                $payment_order->getAmount(),
                //We need a IBAN without spaces
                str_replace(' ', '', $payment_order->getBankInfo()->getIban()),
                $payment_order->getBankInfo()->getAccountOwner()
            );
            if (!empty($payment_order->getBankInfo()->getBic())) {
                $transfer->setBic($payment_order->getBankInfo()->getBic());
            }
            $transfer->setEndToEndIdentification($payment_order->getIDString());
            $transfer->setRemittanceInformation($payment_order->getBankInfo()->getReference());
            $payment->addTransfer($transfer);
        }
    }

    /**
     * This function groups the paymentOrders by bank accounts.
     *
     * @param PaymentOrder[] $payment_orders
     */
    protected function groupByBankAccounts(array $payment_orders): array
    {
        $tmp = [];

        foreach ($payment_orders as $payment_order) {
            $bank_account = $this->getResolvedBankAccount($payment_order);

            //That case should never really happen in reality (except for testing purposes)
            //But as it leads silently to wrong behavior it should throw an exception.
            if (null === $bank_account->getId()) {
                throw new RuntimeException('The associated bank account must be persisted in DB / have an ID to be groupable!');
            }

            //Create entry for bank account if not existing yet
            if (!isset($tmp[$bank_account->getId()])) {
                $tmp[$bank_account->getId()] = [
                    'iban' => $bank_account->getIbanWithoutSpaces(),
                    'bic' => $bank_account->getBic(),
                    'name' => $bank_account->getExportAccountName(),
                    'entries' => [],
                ];
            }

            //Add the current payment order to list
            $tmp[$bank_account->getId()]['entries'][] = $payment_order;
        }

        return $tmp;
    }

    /**
     * Get Bank account for PaymentOrder and resolve FSR-Kom bank account if needed.
     */
    protected function getResolvedBankAccount(PaymentOrder $payment_order): BankAccount
    {
        //Try to resolve FSRKom transactions if possible
        if ($payment_order->isFsrKomResolution()) {
            return $this->getFSRKomBankAccount();
        }

        $bank_account = $payment_order->getDepartment()
->getBankAccount();

        //Throw an error if auto mode is not possible (as bank account definitions are missing)
        if (null === $bank_account) {
            throw new SEPAExportAutoModeNotPossible($payment_order->getDepartment());
        }

        return $bank_account;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
                                   'iban', //The IBAN of the sender
                                   'bic', //The BIC of the sender
                                   'name', //The name of the sender
                               ]);

        $resolver->setAllowedTypes('iban', ['string', 'null']);
        $resolver->setAllowedTypes('bic', ['string', 'null']);
        $resolver->setAllowedTypes('name', ['string', 'null']);

        /* Two different modes, in "manual" all transactions are put in a single payment from the given account data,
           the accounts for the payment order departments are used automatically and are put in (if needed) multiple
           payments from different accounts */
        $resolver->setDefault('mode', 'manual');
        $resolver->setAllowedValues('mode', ['auto', 'manual', 'auto_single']);

        $resolver->setNormalizer('iban', function (Options $options, $value) {
            if (null === $value) {
                return null;
            }
            //Return spaces from IBAN
            return str_replace(' ', '', $value);
        });
    }

    /**
     * Returns the bank account associated with FSR-Kom (this is configured by FSR_KOM_ACCOUNT_ID env).
     */
    public function getFSRKomBankAccount(): BankAccount
    {
        return $this->entityManager->find(BankAccount::class, $this->fsr_kom_bank_account_id);
    }
}
