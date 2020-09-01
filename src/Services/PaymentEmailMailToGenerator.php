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
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

class PaymentEmailMailToGenerator
{
    private $translator;
    private $crudUrlGenerator;

    private $hhv_email;

    public function __construct(TranslatorInterface $translator, CrudUrlGenerator $crudUrlGenerator, string $hhv_email)
    {
        $this->translator = $translator;
        $this->hhv_email = $hhv_email;
        $this->crudUrlGenerator = $crudUrlGenerator;
    }

    public function getHHVMailLink(?PaymentOrder $paymentOrder): ?string
    {
        $string = "mailto:" . urlencode($this->hhv_email);

        //Add subject
        $subject = $this->translator->trans('payment_order.mail.subject') . ' - '
            . urlencode($paymentOrder->getDepartment()->getName()) . ': ' . urlencode($paymentOrder->getProjectName())
            . ' ' . urlencode('[#' . $paymentOrder->getId(). ']');
        $string .= '?subject=' . $subject;

        $content = 'Link: ' . urlencode($this->crudUrlGenerator->build()->setController(PaymentOrderCrudController::class)
            ->setEntityId($paymentOrder->getId())->setAction('detail'));

        $string .= '&body=' . $content;

        return $string;
    }

    /**
     * Generates a "mailto:" string to contact the responsible people for the given payment order.
     * Returns null, if no contact emails are associated with the department.
     * @param  PaymentOrder  $paymentOrder
     * @return string|null
     */
    public function generateMailToHref(PaymentOrder $paymentOrder): ?string
    {
        $emails = $paymentOrder->getDepartment()->getContactEmails();
        if (empty($emails)) {
            return null;
        }

        $string = "mailto:";

        $string .= urlencode(implode(';', $emails));

        //Determine a good email subject
        $subject = $this->translator->trans('payment_order.mail.subject') . ': ' . urlencode($paymentOrder->getProjectName());

        $string .= '?subject=' . $subject;


        return $string;
    }
}