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

namespace App\Controller\Admin;

use App\Admin\Field\CheckField;
use App\Admin\Field\ConfirmationField;
use App\Admin\Field\FieldChangesField;
use App\Admin\Field\VichyFileField;
use App\Admin\Filter\CheckFilter;
use App\Admin\Filter\ConfirmedFilter;
use App\Admin\Filter\DepartmentTypeFilter;
use App\Admin\Filter\MoneyAmountFilter;
use App\Entity\Embeddable\Check;
use App\Entity\FieldChanges;
use App\Entity\PaymentOrder;
use App\Entity\User;
use App\Helpers\ZIPBinaryFileResponseFacade;
use App\Message\PaymentOrder\PaymentOrderDeletedNotification;
use App\Services\EmailConfirmation\ConfirmationEmailSender;
use App\Services\PaymentOrder\CSVExporter;
use App\Services\PaymentOrderMailLinkGenerator;
use App\Services\PaymentReferenceGenerator;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;


#[AdminCrud(routePath: '/payment_order')]
final class PaymentOrderCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly PaymentOrderMailLinkGenerator $mailToGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ConfirmationEmailSender $confirmationEmailSender,
        private readonly AdminUrlGenerator $adminURLGenerator,
        private readonly MessageBusInterface $messageBus,
        private readonly PaymentReferenceGenerator $paymentReferenceGenerator,
        private readonly CSVExporter $CSVExporter,
    )
    {
    }

    public static function getEntityFqcn(): string
    {
        return PaymentOrder::class;
    }

    #[AdminAction(routePath: '/action-csv-export', routeName: 'admin_action_payment_order_csv_export', methods: ['POST'])]
    public function csvExport(BatchActionDto $batchActionDto): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SHOW_PAYMENT_ORDERS');

        $entities = $this->entityManager->getRepository(PaymentOrder::class)->findBy(['id' => $batchActionDto->getEntityIds()]);

        $csv = $this->CSVExporter->export($entities);
        $response = new Response($csv);
        $response->headers->set('Content-type', 'text/csv');
        $response->headers->set('Content-length', (string) strlen($csv));
        $response->headers->set('Cache-Control', 'private');
        $disposition = HeaderUtils::makeDisposition("attachment", "payment_orders.csv", "payment_orders.csv");
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[AdminAction(routePath: '/action-sepa-xml-export', routeName: 'admin_action_payment_order_sepa_xml_export', methods: ['POST'])]
    public function sepaXMLExport(BatchActionDto $batchActionDto): Response
    {
        return $this->redirect(
            $this->adminURLGenerator->setRoute('payment_order_export')
                ->set('ids', implode(',', $batchActionDto->getEntityIds()))
                ->generateUrl()
        );
    }

    #[AdminAction(routePath: '/action-references-export', routeName: 'admin_action_payment_order_references_export', methods: ['POST'])]
    public function referencesExport(BatchActionDto $batchActionDto): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SHOW_PAYMENT_ORDERS');

        $entityManager = $this->entityManager;

        $data = [];
        foreach ($batchActionDto->getEntityIds() as $id) {
            /** @var PaymentOrder $payment_order */
            $payment_order = $entityManager->find($batchActionDto->getEntityFqcn(), $id);
            $path = $payment_order->getReferencesFile()
                ->getPathname();
            $extension = $payment_order->getReferencesFile()
                ->getExtension();



            /*
            if (empty($payment_order->getDepartment()->getReferencesExportPrefix())) {
                $prefix = '';
            } else {
                $prefix = $payment_order->getDepartment()
                        ->getReferencesExportPrefix().'/';
            }*/

            $prefix = '';

            if ($payment_order->getDepartment() !== null && $payment_order->getDepartment()->getBankAccount() !== null) {
                //First folder for each bank account
                $prefix = $payment_order->getDepartment()->getBankAccount()->getName() . ' [' . $payment_order->getDepartment()->getBankAccount()->getIban() .']' . '/';

                //A sub folder for each department
                $prefix .= $payment_order->getDepartment()->getName() . '/';
            } elseif ($payment_order->getDepartment() !== null) {
                $prefix = $payment_order->getDepartment()->getName() . '/';
            }

            $project_name = $payment_order->getProjectName();
            $project_name = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $project_name);
            $project_name = mb_ereg_replace("([\.]{2,})", '', $project_name);
            //Format: "ZA000001 Project Name.pdf"
            $filename = $prefix.$payment_order->getIDString().' '.$project_name.'.'.$extension;

            $data[$filename] = $path;

            if ($this->isGranted('ROLE_EXPORT_PAYMENT_ORDERS_REFERENCES')) {
                //Set exported status
                $payment_order->setReferencesExported(true);
            }
        }

        if ($this->isGranted('ROLE_EXPORT_PAYMENT_ORDERS_REFERENCES')) {
            //Flush changes
            $this->entityManager->flush();
        }

        return ZIPBinaryFileResponseFacade::createZIPResponseFromFiles(
            $data,
            'Belege_'.date('Y-m-d_H-i-s').'.zip');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud

            //Set validation groups for new and edit forms
            ->setFormOptions([
                'validation_groups' => ['Default', 'backend'],
            ])

            ->setEntityLabelInSingular('payment_order.label')
            ->setEntityLabelInPlural('payment_order.labelp')
            ->setSearchFields(['id', 'submitter_name', 'project_name', 'funding_id', 'submitter_email', 'amount', 'comment', 'bank_info.account_owner', 'bank_info.street', 'bank_info.zip_code', 'bank_info.city', 'bank_info.iban', 'bank_info.bic', 'bank_info.bank_name', 'bank_info.reference']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('department', 'payment_order.department.label'))
            ->add(DepartmentTypeFilter::new('department_type', 'payment_order.department_type.label'))
            ->add(MoneyAmountFilter::new('amount', 'payment_order.amount.label'))
            ->add(CheckFilter::new('factually_correct', 'payment_order.factually_correct.label'))
            ->add(CheckFilter::new('mathematically_correct', 'payment_order.mathematically_correct.label'))
            ->add(BooleanFilter::new('exported', 'payment_order.exported.label'))
            ->add(ConfirmedFilter::new('confirmed', 'payment_order.confirmed.label'))
            ->add(TextFilter::new('funding_id', 'payment_order.funding_id.label'))
            ->add(DateTimeFilter::new('creation_date', 'creation_date'))
            ->add(DateTimeFilter::new('last_modified', 'last_modified'))
            ->add(DateTimeFilter::new('booking_date', 'payment_order.booking_date.label'))
            ->add(BooleanFilter::new('references_exported', 'payment_order.references_exported.label'))
            ;
    }

    /**
     * Handler for action if user click "resend" button in admin page.
     */
    #[AdminAction(routePath: '/action-resend-confirmation', routeName: 'admin_action_payment_order_resend_confirmation', methods: ['POST', 'GET'])]
    public function resendConfirmationEmail(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('ROLE_EDIT_PAYMENT_ORDERS');
        $payment_order = $context->getEntity()
            ->getInstance();

        $this->confirmationEmailSender->resendConfirmations($payment_order);

        $this->addFlash('success', 'payment_order.action.resend_confirmation.success');

        return $this->redirectToRoute('admin_payment_order_detail', ['entityId' => $payment_order->getId()]);
    }

    /**
     * Handler for action if the user clicks on the "update reference" button in the admin page.
     * This should regenerate the reference from the invoice and reference number of the payment order.
     * @param  AdminContext  $context
     * @return Response
     */
    #[AdminAction(routePath: '/action-update-reference', routeName: 'admin_action_payment_order_update_reference', methods: ['POST', 'GET'])]
    public function updateReference(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('ROLE_EDIT_PAYMENT_ORDERS');

        $payment_order = $context->getEntity()->getInstance();

        $this->paymentReferenceGenerator->setPaymentReference($payment_order);
        $this->entityManager->flush();

        return $this->redirectToRoute('admin_payment_order_edit', ['entityId' => $payment_order->getId()]);
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile('js/admin/apply_row_color.js');
    }

    public function configureActions(Actions $actions): Actions
    {
        if ($this->isGranted('ROLE_EXPORT_PAYMENT_ORDERS')) {
            // Button with text and icon
            $actions->addBatchAction(Action::new('sepaXMLExport', 'payment_order.action.export_xml')
                ->linkToCrudAction('sepaXMLExport')
                ->addCssClass('btn btn-primary')
                //Together with some backend.js logic, this attribute will prevent the modal from showing
                ->setHtmlAttributes([
                    'data-action-batch-no-confirm' => 'true',
                ])
                ->setIcon('fas fa-file-export')
            );
        }

        //if ($this->isGranted('ROLE_EXPORT_PAYMENT_ORDERS_REFERENCES')) {
        $actions->addBatchAction(Action::new('referencesExport', 'payment.order.action.export.export_references')
            ->linkToCrudAction('referencesExport')
            ->addCssClass('btn btn-primary')
            //Together with some backend.js logic, this attribute will prevent the modal from showing
            ->setHtmlAttributes([
                'data-action-batch-no-confirm' => 'true',
            ])
            ->setIcon('fas fa-file-invoice')
        );
        //}

        if ($this->isGranted('ROLE_EXPORT_PAYMENT_ORDERS')) {
        $actions->addBatchAction(Action::new('csvExport', 'CSV Export')
            ->linkToCrudAction('csvExport')
            ->addCssClass('btn btn-secondary')
            //Together with some backend.js logic, this attribute will prevent the modal from showing
            ->setHtmlAttributes([
                'data-action-batch-no-confirm' => 'true',
            ])
            ->setIcon('fas fa-file-csv')
        );
        }


        $actions->setPermissions([
            Action::INDEX => 'ROLE_SHOW_PAYMENT_ORDERS',
            Action::DETAIL => 'ROLE_SHOW_PAYMENT_ORDERS',
            Action::EDIT => 'ROLE_EDIT_PAYMENT_ORDERS',
            Action::DELETE => 'ROLE_EDIT_PAYMENT_ORDERS',
            Action::NEW => 'ROLE_EDIT_PAYMENT_ORDERS',
        ]);

        $emailAction = Action::new('sendEmail', 'payment_order.action.email', 'fas fa-envelope')
            ->linkToUrl(fn(PaymentOrder $paymentOrder): string => $this->mailToGenerator->generateContactMailLink($paymentOrder))
            ->setCssClass('btn btn-secondary');

        //Hide action if no contact emails are associated with department
        //$emailAction->displayIf(fn(PaymentOrder $paymentOrder): bool => null !== $this->mailToGenerator->generateContactMailLink($paymentOrder));

        $resend_confirmation_action = Action::new('resendConfirmation', 'payment_order.action.resend_confirmation', 'fas fa-redo')
            ->linkToCrudAction('resendConfirmationEmail')
            ->displayIf(fn(PaymentOrder $paymentOrder): bool => $this->isGranted('ROLE_EDIT_PAYMENT_ORDERS') && !$paymentOrder->isConfirmed())
            ->setCssClass('btn btn-secondary');

        $mathematically_correct_action = Action::new('mathematicallyCorrect', 'payment_order.action.mathematically_correct', 'fas fa-check')
            ->linkToRoute('payment_order_check', fn (PaymentOrder $paymentOrder) => ['type' => 'mathematically_correct', 'id' => $paymentOrder->getId()])
            ->displayIf(fn(PaymentOrder $paymentOrder): bool => $this->isGranted('ROLE_PO_MATHEMATICALLY')
                && $paymentOrder->isConfirmed()
                && !$paymentOrder->isMathematicallyCorrectChecked())
            ->setCssClass('btn btn-success');

        $factually_correct_action = Action::new('factuallyCorrect', 'payment_order.action.factually_correct', 'fas fa-check')
            ->linkToRoute('payment_order_check', fn (PaymentOrder $paymentOrder) => ['type' => 'factually_correct', 'id' => $paymentOrder->getId()])
            ->displayIf(fn(PaymentOrder $paymentOrder): bool => $this->isGranted('ROLE_PO_FACTUALLY')
                && $paymentOrder->isConfirmed()
                && !$paymentOrder->isFactuallyCorrectChecked()
                && $paymentOrder->isMathematicallyCorrectChecked())
            ->setCssClass('btn btn-success');

        $manual_confirmation = Action::new('manual_confirmation', 'payment_order.action.manual_confirmation', 'fas fa-exclamation-triangle')
            ->setCssClass('btn btn-secondary')
            ->linkToRoute('payment_order_manual_confirm', fn(PaymentOrder $paymentOrder): array => [
                'id' => $paymentOrder->getId(),
            ])
            ->displayIf(fn(PaymentOrder $paymentOrder): bool => $this->isGranted('ROLE_MANUAL_CONFIRMATION')
                && !$paymentOrder->isConfirmed());

        $pdf_form_action = Action::new('pdf_form', 'payment_order.action.pdf_form', 'fas fa-file-contract')
            ->linkToRoute('payment_order_pdf_stura', fn(PaymentOrder $paymentOrder): array => [
                'id' => $paymentOrder->getId(),
            ])
            ->displayIf(fn(PaymentOrder $paymentOrder): bool => $paymentOrder->isFactuallyCorrectChecked() || $paymentOrder->isMathematicallyCorrectChecked())
            ->setCssClass('btn btn-info');

        $regenerate_reference = Action::new('update_reference', 'Verwendungszweck aktualisieren', 'fas fa-arrows-rotate')
            ->linkToCrudAction('updateReference');

        $actions->add(Crud::PAGE_EDIT, $emailAction);
        $actions->add(Crud::PAGE_DETAIL, $emailAction);


        $actions->disable(Crud::PAGE_NEW);

        if(!$this->isGranted('ROLE_EDIT_PAYMENT_ORDERS')) {
            $actions->disable('batchDelete');
        }

        $actions->add(Crud::PAGE_DETAIL, $resend_confirmation_action);
        $actions->add(Crud::PAGE_EDIT, $resend_confirmation_action);

        $actions->add(Crud::PAGE_EDIT, $regenerate_reference);

        $actions->add(Crud::PAGE_DETAIL, $mathematically_correct_action);
        $actions->add(Crud::PAGE_DETAIL, $factually_correct_action);

        $actions->add(Crud::PAGE_DETAIL, $manual_confirmation);
        $actions->add(Crud::PAGE_EDIT, $manual_confirmation);

        $actions->add(Crud::PAGE_DETAIL, $pdf_form_action);
        $actions->add(Crud::PAGE_EDIT, $pdf_form_action);

        $actions->add(Crud::PAGE_INDEX, Action::DETAIL);

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        //Documents
        $documentsPanel = FormField::addFieldset('payment_order.group.documents');
        $printed_form = VichyFileField::new('printed_form_file', 'payment_order.printed_form.label');
        $references = VichyFileField::new('references_file', 'payment_order.references.label');

        //Basic informations
        $infoPanel = FormField::addFieldset('payment_order.group.info');
        $id = IntegerField::new('id', 'payment_order.id.label');
        $submitterName = TextField::new('submitter_name', 'Name Auftraggeber');
        $submitterEmail = EmailField::new('submitter_email', 'payment_order.contact_email.label')
            ->setFormTypeOption('empty_data', '')
            ->setRequired(false);

        if (Crud::PAGE_INDEX === $pageName) {
            $tmp = 'payment_order.department.label_short';
        } else {
            $tmp = 'payment_order.department.label';
        }

        $department = AssociationField::new('department', $tmp)
            ->setRequired(true)
            //->autocomplete()
            ->setFormTypeOption('attr', [
                'data-widget' => 'select2',
                'data-allow-clear' => false,
                'required' => 'required'
            ]);

        $amount = MoneyField::new('amount', 'payment_order.amount.label')
            ->setCurrency('EUR')
            ->setStoredAsCents(true);
        $projectName = TextField::new('project_name', 'payment_order.project_name.label');
        $funding_id = TextField::new('funding_id', 'payment_order.funding_id.label')
            ->setRequired(false)
            ->setFormTypeOption('empty_data', '');
        //Use short name for index
        $funding_id_index = TextField::new('funding_id', 'payment_order.funding_id.label_short')
            ->setHelp('payment_order.funding_id.label');
        $fsr_kom = BooleanField::new('fsr_kom_resolution', 'payment_order.fsr_kom.label')
            ->setRequired(false);
        $resolution_date = DateField::new('resolution_date', 'payment_order.resolution_date.label')
            ->setFormTypeOption('view_timezone', 'UTC') //Set timezone to UTC, as its saved that way in the database, otherwise the date will be marked as changed
            ->setFormTypeOption('model_timezone', 'UTC') //Set timezone to UTC, as its saved that way in the database
            ->setRequired(false);

        //Supporting values
        $supportingAmount = MoneyField::new('supporting_amount', 'Unterstützender Betrag')
            ->setCurrency('EUR')
            ->setStoredAsCents();
        $supportingFundingID = TextField::new('supporting_funding_id', 'Unterstützende Mittelfreigabe')
            ->setRequired(false);
        $supportingFundingDate = DateField::new('supporting_funding_date', 'Datum der unterstützenden MF')
            ->setFormTypeOption('view_timezone', 'UTC') //Set timezone to UTC, as its saved that way in the database, otherwise the date will be marked as changed
            ->setFormTypeOption('model_timezone', 'UTC') //Set timezone to UTC, as its saved that way in the database
            ->setRequired(false);

        $comment = TextareaField::new('comment', 'payment_order.comment.label')
            ->setRequired(false)
            ->setFormTypeOption('empty_data', '');
        $lastModified = DateTimeField::new('last_modified', 'last_modified');
        $creationDate = DateTimeField::new('creation_date', 'creation_date')
            ->setTemplatePath('admin/field/datetime_overdue_hint.html.twig');
        //$creationDate = TextField::new('creation_date', 'creation_date');

        //Status informations
        $statusPanel = FormField::addFieldset('payment_order.group.status');
        $mathematicallyCorrect = BooleanField::new('mathematically_correct.checked', 'payment_order.mathematically_correct.label')
            ->setHelp('payment_order.mathematically_correct.help')
            //Disable fields (and show coloumns as read only tags) if user does not have proper permissions to change
            //factually and mathematically correct status
            ->setFormTypeOption('disabled', !$this->isGranted('ROLE_PO_MATHEMATICALLY'))
            ->renderAsSwitch($this->isGranted('ROLE_PO_MATHEMATICALLY'));
        $exported = BooleanField::new('exported', 'payment_order.exported.label')
            ->setHelp('payment_order.exported.help');
        $factuallyCorrect = BooleanField::new('factually_correct.checked', 'payment_order.factually_correct.label')
            ->setHelp('payment_order.factually_correct.help')
            ->setFormTypeOption('disabled', !$this->isGranted('ROLE_PO_FACTUALLY'))
            ->renderAsSwitch($this->isGranted('ROLE_PO_FACTUALLY'));
        $booking_date = DateTimeField::new('booking_date', 'payment_order.booking_date.label');

        $requiredConfirmations = NumberField::new('required_confirmations', 'payment_order.required_confirmations.label');
        $confirmation1 = ConfirmationField::new('confirmation1', 'payment_order.confirmation1.label');
        $confirmation2 = ConfirmationField::new('confirmation2', 'payment_order.confirmation2.label');

        $references_exported = BooleanField::new('references_exported', 'payment_order.references_exported.label');

        //Payee informations
        $payeePanel = FormField::addFieldset('payment_order.group.receiver');
        $bankInfoAccountOwner = TextField::new('bank_info.account_owner', 'bank_info.account_owner.label');
        $bankInfoStreet = TextField::new('bank_info.street', 'bank_info.street.label');
        $bankInfoZipCode = TextField::new('bank_info.zip_code', 'bank_info.zip_code.label');
        $bankInfoCity = TextField::new('bank_info.city', 'bank_info.city.label');

        //Payee bank account infos
        $bankInfoPanel = FormField::addFieldset('payment_order.group.bank_info');
        $bankInfoIban = TextField::new('bank_info.iban', 'bank_info.iban.label')
        ->formatValue(fn($value, PaymentOrder $entity) => $entity->getBankInfo()->getIbanFormatted());
        $bankInfoBic = TextField::new('bank_info.bic', 'bank_info.bic.label')
            ->setRequired(false)
            ->setFormTypeOption('empty_data', '');
        $bankInfoBankName = TextField::new('bank_info.bank_name', 'bank_info.bank_name.label');
        $bankInfoReference = TextField::new('bank_info.reference', 'bank_info.reference.label')
            ->setRequired(false)
            ->setFormTypeOption('empty_data', '');
        $invoiceNumber = TextField::new('invoice_number', 'payment_order.invoice_number.label')
            ->setRequired(false);
        $customerNumber = TextField::new('customer_number', 'payment_order.customer_number.label')
            ->setRequired(false)
            ->setHelp('Wenn die Rechnungs- oder Kundennummer geändert wird, muss der Verwendungszweck aktualisiert werden (Button "Verwendungszweck aktualisieren")!');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $projectName, $department, $amount, $mathematicallyCorrect, $factuallyCorrect, $funding_id_index, $creationDate];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                FormField::addTab('payment_order.tab.info', 'fas fa-circle-info'),


                //Documents section
                $documentsPanel,
                $printed_form,
                $references,

                //Basic informations
                FormField::addColumn(),
                $infoPanel,
                $id,
                $submitterName,
                $submitterEmail,
                $department,
                $projectName,
                $funding_id,
                $amount,
                $resolution_date,
                $supportingAmount,
                $supportingFundingID,
                $supportingFundingDate,
                $comment,
                $lastModified,
                $creationDate,
                $fsr_kom,

                //Payee informations
                FormField::addColumn(),
                $payeePanel,
                $bankInfoAccountOwner,
                $bankInfoStreet,
                $bankInfoZipCode,
                $bankInfoCity,

                //Banking informations
                $bankInfoPanel,
                $bankInfoIban,
                $bankInfoBic,
                $bankInfoBankName,
                $bankInfoReference,
                $invoiceNumber,
                $customerNumber,


                FormField::addTab('payment_order.tab.status', 'fas fa-list-check'),
                //Status infos
                FormField::addColumn(),
                FormField::addFieldset('payment_order.section.status.confirmation'),
                BooleanField::new('confirmed', 'payment_order.confirmed.label'),
                $requiredConfirmations,
                $confirmation1,
                $confirmation2,

                FormField::addColumn(),
                FormField::addFieldset('payment_order.section.status.review'),
                CheckField::new('mathematically_correct', 'payment_order.mathematically_correct.label'),
                CheckField::new('factually_correct', 'payment_order.factually_correct.label'),
                $exported,
                $booking_date,
                $references_exported,

                FormField::addFieldset('payment_order.section.edited_fields'),
                FieldChangesField::new('field_changes', ""),

            ];
        }

        if (Crud::PAGE_EDIT === $pageName) {
            return [
                FormField::addTab('payment_order.tab.info', 'fas fa-circle-info'),

                //Basic informations
                FormField::addColumn(),
                $infoPanel,
                $submitterName,
                $submitterEmail,
                $department,
                $projectName,
                $funding_id,
                $amount,
                $resolution_date,
                $supportingAmount,
                $supportingFundingID,
                $supportingFundingDate,
                $comment,
                $fsr_kom,

                //Payee informations
                FormField::addColumn(),
                $payeePanel,
                $bankInfoAccountOwner,
                $bankInfoStreet,
                $bankInfoZipCode,
                $bankInfoCity,
                //Banking informations
                $bankInfoPanel,
                $bankInfoIban,
                $bankInfoBic,
                $bankInfoBankName,
                $bankInfoReference,
                $invoiceNumber,
                $customerNumber,


                FormField::addTab('payment_order.tab.status', 'fas fa-list-check'),
                //Status infos
                $statusPanel,
                $mathematicallyCorrect,
                $exported,
                $factuallyCorrect,
                $references_exported,


                FormField::addTab('Dokumente', 'fas fa-file'),
                //Documents section
                $documentsPanel,
                $printed_form,
                $references,
            ];
        }

        throw new RuntimeException('It should not be possible to reach this point...');
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$this->isGranted('ROLE_EDIT_PAYMENT_ORDERS')) {
            $this->addFlash('error', 'You are not allowed to delete Payment Orders!');
            return;
        }

        /** @var PaymentOrder $entityInstance */
        //Forbit delete process if PaymentOrder was already exported or booked
        if ($entityInstance->isExported()
            || null != $entityInstance->getBookingDate()) {
            $this->addFlash('warning', 'payment_order.flash.can_not_delete_checked_payment_order');

            return;
        }

        //Send a notification to FSR officers that payment order was deleted
        $blame_user = 'unknown';
        $user = $this->getUser();
        if ($user instanceof User) {
            $blame_user = $user->getFullName();
        }
        $message = new PaymentOrderDeletedNotification($entityInstance, $blame_user, PaymentOrderDeletedNotification::DELETED_WHERE_BACKEND);
        $this->messageBus->dispatch($message);

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
