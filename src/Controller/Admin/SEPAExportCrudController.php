<?php

namespace App\Controller\Admin;

use App\Admin\Field\VichyFileField;
use App\Admin\Filter\MoneyAmountFilter;
use App\Admin\Filter\ULIDFilter;
use App\Entity\SEPAExport;
use App\Services\SEPAExport\SEPAExportAdminHelper;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class SEPAExportCrudController extends AbstractCrudController
{
    private $adminHelper;
    private $translator;
    private $entityManager;

    public function __construct(SEPAExportAdminHelper $adminHelper, TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        $this->adminHelper = $adminHelper;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
    }

    public function bookPaymentOrders(AdminContext $context): Response
    {
        /** @var SEPAExport $entity */
        $entity = $context->getEntity()->getInstance();

        $entity_must_be_booked = true;

        if(!$entity->isOpen()) {
            $entity_must_be_booked = false;
            $this->addFlash('error', 'sepa_export.flash.sepa_export_already_booked');
        }

        $not_factually = $this->adminHelper->getNotFactuallyCorrectPaymentOrders($entity);
        if (!empty($not_factually)) {
            $entity_must_be_booked = false;
            $this->addFlash('warning', $this->translator->trans('sepa_export.flash.sepa_export_payments_not_factually_checked', ['%payment_orders%' => $this->adminHelper->getPaymentOrdersFlashText($not_factually)]));
        }

        $not_mathematically = $this->adminHelper->getNotMathematicallyCorrectPaymentOrders($entity);
        if (!empty($not_mathematically)) {
            $entity_must_be_booked = false;
            $this->addFlash('warning', $this->translator->trans('sepa_export.flash.sepa_export_payments_not_mathematically_checked', ['%payment_orders%' => $this->adminHelper->getPaymentOrdersFlashText($not_mathematically)]));
        }

        $already_booked = $this->adminHelper->getAlreadyBookedPaymentOrders($entity);
        if (!empty($already_booked)) {
            $entity_must_be_booked = false;
            $this->addFlash('warning', $this->translator->trans('sepa_export.flash.sepa_export_payments_already_booked', ['%payment_orders%' => $this->adminHelper->getPaymentOrdersFlashText($already_booked)]));
        }

        if ($entity_must_be_booked) {
            $entity->setIsBooked();
            //Book all associated payment orders
            foreach ($entity->getAssociatedPaymentOrders() as $paymentOrder) {
                $paymentOrder->setBookingDate(new \DateTime());
            }
            $this->entityManager->flush();
            $this->addFlash('success', 'sepa_export.flash.sepa_export_books_success');
        }

        return $this->redirect($context->getReferrer() ?? '/admin');
    }

    public static function getEntityFqcn(): string
    {
        return SEPAExport::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('sepa_export.label')
            ->showEntityActionsInlined()
            ->setSearchFields(['description', 'initiator_iban', 'initiator_bic', 'comment', 'sepa_message_id'])
            ->setEntityLabelInPlural('sepa_export.labelp');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(NumericFilter::new('number_of_payments', 'sepa_export.number_of_payments'))
            ->add(MoneyAmountFilter::new('total_sum', 'sepa_export.total_sum'))
            ->add(TextFilter::new('description', 'sepa_export.description'))
            ->add(TextFilter::new('initiator_iban', 'sepa_export.initiator_iban'))
            ->add(TextFilter::new('initiator_bic', 'sepa_export.initiator_bic'))
            ->add(TextFilter::new('sepa_message_id', 'sepa_export.message_id'))
            ->add(DateTimeFilter::new('creation_date', 'creation_date'))
            ->add(DateTimeFilter::new('last_modified', 'last_modified'))
            ->add(DateTimeFilter::new('booking_date', 'sepa_export.booking_date'))
            ->add(ULIDFilter::new('group_ulid', 'sepa_export.group_ulid'))
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->setPermissions([
            Action::DETAIL => 'ROLE_SHOW_SEPA_EXPORTS',
            Action::INDEX => 'ROLE_SHOW_SEPA_EXPORTS',
            Action::EDIT => 'ROLE_EDIT_SEPA_EXPORTS',
            Action::DELETE => 'ROLE_EDIT_SEPA_EXPORTS',
        ]);

        $book_action = Action::new('bookPaymentOrders', 'sepa_export.action.book_payment_orders', 'fas fa-check')
            ->linkToCrudAction('bookPaymentOrders')
            /*->displayIf(function (PaymentOrder $paymentOrder) {
                return $this->isGranted('ROLE_PO_FACTUALLY')
                    && $paymentOrder->isConfirmed()
                    && !$paymentOrder->isFactuallyCorrect()
                    && $paymentOrder->isMathematicallyCorrect();
            })*/
            ->setCssClass('btn btn-success');
            $actions->add(Crud::PAGE_DETAIL, $book_action);

        $actions->disable(Crud::PAGE_NEW);
        $actions->add(Crud::PAGE_INDEX, Action::DETAIL);

        return parent::configureActions($actions); // TODO: Change the autogenerated stub
    }

    public function configureFields(string $pageName): iterable
    {
        $xml_file = VichyFileField::new('xml_file', 'sepa_export.xml_file');
        $id = IdField::new('id', 'sepa_export.id');
        $number_of_payments = NumberField::new('number_of_payments', 'sepa_export.number_of_payments')
            ->setHelp('sepa_export.number_of_payments.help');;
        $initiator_bic = TextField::new('initiator_bic', 'sepa_export.initiator_bic')
            ->setHelp('sepa_export.initiator_bic.help');
        $initiator_iban = TextField::new('initiator_iban', 'sepa_export.initiator_iban')
            ->setHelp('sepa_export.initiator_iban.help');
        $booking_date = DateTimeField::new('booking_date', 'sepa_export.booking_date');
        $total_sum = MoneyField::new('total_sum', 'sepa_export.total_sum')
            ->setCurrency('EUR')
            ->setStoredAsCents(true)
            ->setHelp('sepa_export.total_sum.help');;
        $sepa_message_id = TextField::new('sepa_message_id', 'sepa_export.message_id')
            ->setHelp('sepa_export.message_id.help');
        $description = TextField::new('description', 'sepa_export.description');
        $comment = TextEditorField::new('comment', 'sepa_export.comment');
        $group_ulid = TextField::new('group_ulid', 'sepa_export.group_ulid')
            ->setHelp('sepa_export.group_ulid.help')
            ->setTemplatePath('admin/field/group_ulid.html.twig');
        $last_modified = DateTimeField::new('last_modified', 'last_modified');
        $creationDate = DateTimeField::new('creation_date', 'creation_date')->onlyOnDetail();
        $associated_payment_orders = AssociationField::new('associated_payment_orders')
            ->setTemplatePath('admin/field/payment_orders_association.html.twig')
            ->setCrudController(PaymentOrderCrudController::class);


        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $number_of_payments, $total_sum, $description, $initiator_iban, $booking_date];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $xml_file,

                FormField::addPanel('sepa_export.infos')->collapsible(),
                $id,
                $number_of_payments,
                $total_sum,
                $initiator_iban,
                $initiator_bic,
                $description,
                $booking_date,
                $associated_payment_orders,
                $comment,

                FormField::addPanel('sepa_export.advanced')->collapsible(),
                $sepa_message_id,
                $group_ulid,
                $last_modified,
                $creationDate,
            ];
        }

        if (Crud::PAGE_EDIT === $pageName) {
            return [$comment];
        }

        throw new \LogicException("This should never be reached!");
    }
}
