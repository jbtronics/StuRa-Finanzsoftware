<?php


namespace App\Services;


use App\Entity\PaymentOrder;
use Symfony\Contracts\Translation\TranslatorInterface;

class PaymentEmailMailToGenerator
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
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