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

use App\Controller\Admin\PaymentOrderCrudController;
use App\Entity\PaymentOrder;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use LogicException;
use SteveGrunwell\MailToLinkFormatter\MailTo;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This service generates email links for a payment order, including adresses, subject and body.
 */
class PaymentOrderMailLinkGenerator
{
    private $translator;
    private $adminURLGenerator;

    private $hhv_email;

    public function __construct(TranslatorInterface $translator, AdminUrlGenerator $adminUrlGenerator, string $hhv_email)
    {
        $this->translator = $translator;
        $this->hhv_email = $hhv_email;
        $this->adminURLGenerator = $adminUrlGenerator;
    }

    /**
     * Generates a "mailto:" string to contact the HHV for the given payment order. It includes a link to the payment
     * order.
     * If no payment order is passed (null) only the mailto: link for the HHV email is returned.
     */
    public function getHHVMailLink(?PaymentOrder $paymentOrder = null): string
    {
        $mailTo = new MailTo();
        $mailTo->setRecipients($this->hhv_email);

        if (null !== $paymentOrder) {
            //Add subject
            $mailTo->setHeader('subject', $this->getSubject($paymentOrder));

            $content = 'Link: '.
                $this->adminURLGenerator->setController(PaymentOrderCrudController::class)
                    ->setEntityId($paymentOrder->getId())
                    ->setAction('detail')
                    ->removeReferrer()
                    ->unset('filters');

            $mailTo->setBody($content);
        }

        return $mailTo->getLink();
    }

    /**
     * Generates a "mailto:" string to contact the responsible people for the given payment order.
     * Returns null, if no contact emails are associated with the department.
     */
    public function generateContactMailLink(PaymentOrder $paymentOrder): string
    {
        $mailTo = new MailTo();

        if (!empty($paymentOrder->getContactEmail())) {
            $mailTo->setRecipients($paymentOrder->getContactEmail());
        } elseif (!empty($paymentOrder->getDepartment()->getContactEmails())) {
            $mailTo->setRecipients($paymentOrder->getDepartment()->getContactEmails());
        } else {
            throw new LogicException('No recipeint could be determined for this payment order!');
        }

        $mailTo->setHeader('subject', $this->getSubject($paymentOrder));

        return $mailTo->getLink();
    }

    /**
     * Determines a good subject for an email.
     */
    protected function getSubject(PaymentOrder $paymentOrder): string
    {
        return sprintf(
            '%s - %s: %s [%s]',
            $this->translator->trans('payment_order.mail.subject'),
            $paymentOrder->getDepartment()
                ->getName(),
            $paymentOrder->getProjectName(),
            $paymentOrder->getIDString()
        );
    }
}
