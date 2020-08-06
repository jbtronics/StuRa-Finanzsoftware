<?php


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