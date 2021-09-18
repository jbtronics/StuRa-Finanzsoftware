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

use App\Admin\Field\VichyFileField;
use App\Admin\Filter\ConfirmedFilter;
use App\Admin\Filter\DepartmentTypeFilter;
use App\Admin\Filter\MoneyAmountFilter;
use App\Entity\PaymentOrder;
use App\Helpers\ZIPBinaryFileResponseFacade;
use App\Services\EmailConfirmation\ConfirmationEmailSender;
use App\Services\PaymentOrderMailLinkGenerator;
use Doctrine\ORM\EntityManagerInterface;
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
use Symfony\Component\HttpFoundation\Response;

class PaymentOrderCrudController extends AbstractCrudController
{
    private $mailToGenerator;
    private $dashboardControllerRegistry;
    private $confirmationEmailSender;
    private $adminURLGenerator;
    private $entityManager;

    public function __construct(PaymentOrderMailLinkGenerator $mailToGenerator,
        DashboardControllerRegistry $dashboardControllerRegistry, EntityManagerInterface $entityManager,
        ConfirmationEmailSender $confirmationEmailSender, AdminUrlGenerator $adminUrlGenerator)
    {
        $this->mailToGenerator = $mailToGenerator;
        $this->dashboardControllerRegistry = $dashboardControllerRegistry;
        $this->confirmationEmailSender = $confirmationEmailSender;
        $this->adminURLGenerator = $adminUrlGenerator;
        $this->entityManager = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return PaymentOrder::class;
    }

    public function sepaXMLExport(BatchActionDto $batchActionDto): Response
    {
        return $this->redirect(
            $this->adminURLGenerator->setRoute('payment_order_export')
                ->set('ids', implode(',', $batchActionDto->getEntityIds()))
                ->generateUrl()
        );
    }

    public function referencesExport(BatchActionDto $batchActionDto): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SHOW_PAYMENT_ORDERS');

        $entityManager = $this->getDoctrine()->getManagerForClass($batchActionDto->getEntityFqcn());

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
                $prefix = $payment_order->getDepartment()->getBankAccount()->getName() . '/';

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
            ->setEntityLabelInSingular('payment_order.label')
            ->setEntityLabelInPlural('payment_order.labelp')
            ->setSearchFields(['id', 'first_name', 'last_name', 'project_name', 'funding_id', 'contact_email', 'amount', 'comment', 'bank_info.account_owner', 'bank_info.street', 'bank_info.zip_code', 'bank_info.city', 'bank_info.iban', 'bank_info.bic', 'bank_info.bank_name', 'bank_info.reference']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('department', 'payment_order.department.label'))
            ->add(DepartmentTypeFilter::new('department_type', 'payment_order.department_type.label'))
            ->add(MoneyAmountFilter::new('amount', 'payment_order.amount.label'))
            ->add(BooleanFilter::new('factually_correct', 'payment_order.factually_correct.label'))
            ->add(BooleanFilter::new('exported', 'payment_order.exported.label'))
            ->add(BooleanFilter::new('mathematically_correct', 'payment_order.mathematically_correct.label'))
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
    public function resendConfirmationEmail(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('ROLE_EDIT_PAYMENT_ORDERS');
        $payment_order = $context->getEntity()
            ->getInstance();

        $this->confirmationEmailSender->resendConfirmations($payment_order);

        $this->addFlash('success', 'payment_order.action.resend_confirmation.success');

        return $this->redirect($context->getReferrer() ?? '/admin');
    }

    /**
     * Handler for action if user click "check mathematically" button in admin page.
     */
    public function checkMathematicallyCorrect(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PO_MATHEMATICALLY');

        /** @var PaymentOrder $payment_order */
        $payment_order = $context->getEntity()
            ->getInstance();
        $payment_order->setMathematicallyCorrect(true);
        $this->entityManager->flush();
        $this->addFlash('success', 'payment_order.action.mathematically_correct.success');

        return $this->redirect($context->getReferrer() ?? '/admin');
    }

    /**
     * Handler for action if user click "check factually" button in admin page.
     */
    public function checkFactuallyCorrect(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PO_FACTUALLY');

        /** @var PaymentOrder $payment_order */
        $payment_order = $context->getEntity()
            ->getInstance();
        $payment_order->setFactuallyCorrect(true);
        $this->entityManager->flush();
        $this->addFlash('success', 'payment_order.action.factually_correct.success');

        return $this->redirect($context->getReferrer() ?? '/admin');
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
                ->setHtmlAttributes([
                    /*'onclick' => '$("#modal-batch-action").on("shown.bs.modal", function(e){
                        $("#modal-batch-action").addClass("d-none");
                        $("#modal-batch-action-button").trigger("click");
                    });'*/
                    //Very ugly hack to skip the confirmation dialog.
                    'onclick' => '
                        let $actionElement = $(this);
                        $("#modal-batch-action").addClass("d-none");
                        $actionElement.off("click");

                        const actionName = $actionElement.attr("data-action-name");
                        const selectedItems = $("input[type=\'checkbox\'].form-batch-checkbox:checked");
               
                        $form = document.createElement("form");
                        $form.setAttribute("action", $actionElement.attr("data-action-url"));
                        $form.setAttribute("method", "POST");

                        $actionNameInput = document.createElement("input");
                        $actionNameInput.setAttribute("type", "hidden");
                        $actionNameInput.setAttribute("name", "batchActionName");
                        $actionNameInput.setAttribute("value", $actionElement.attr("data-action-name"));
                        $form.appendChild($actionNameInput);

                        $entityFqcnInput = document.createElement("input");
                        $entityFqcnInput.setAttribute("type", "hidden");
                        $entityFqcnInput.setAttribute("name", "entityFqcn");
                        $entityFqcnInput.setAttribute("value", $actionElement.attr("data-entity-fqcn"));
                        $form.appendChild($entityFqcnInput);

                        $actionUrlInput = document.createElement("input");
                        $actionUrlInput.setAttribute("type", "hidden");
                        $actionUrlInput.setAttribute("name", "batchActionUrl");
                        $actionUrlInput.setAttribute("value", $actionElement.attr("data-action-url"));
                        $form.appendChild($actionUrlInput);

                        $csrfTokenInput = document.createElement("input");
                        $csrfTokenInput.setAttribute("type", "hidden");
                        $csrfTokenInput.setAttribute("name", "batchActionCsrfToken");
                        $csrfTokenInput.setAttribute("value", $actionElement.attr("data-action-csrf-token"));
                        $form.appendChild($csrfTokenInput);

                        selectedItems.each((i, item) => {
                            $entityIdInput = document.createElement("input");
                            $entityIdInput.setAttribute("type", "hidden");
                            $entityIdInput.setAttribute("name", `batchActionEntityIds[${i}]`);
                            $entityIdInput.setAttribute("value", item.value);
                            $form.appendChild($entityIdInput);
                        });

                        document.body.appendChild($form);

                        //modalTitle.text(titleContentWithPlaceholders);
                        $form.submit();
                    '
                ])
                ->setIcon('fas fa-file-export')
            );
        }

        //if ($this->isGranted('ROLE_EXPORT_PAYMENT_ORDERS_REFERENCES')) {
            $actions->addBatchAction(Action::new('referencesExport', 'payment.order.action.export.export_references')
                    ->linkToCrudAction('referencesExport')
                    ->addCssClass('btn btn-primary')
                    ->setIcon('fas fa-file-invoice')
            );
        //}

        $actions->setPermissions([
            Action::INDEX => 'ROLE_SHOW_PAYMENT_ORDERS',
            Action::DETAIL => 'ROLE_SHOW_PAYMENT_ORDERS',
            Action::EDIT => 'ROLE_EDIT_PAYMENT_ORDERS',
            Action::DELETE => 'ROLE_EDIT_PAYMENT_ORDERS',
            Action::NEW => 'ROLE_EDIT_PAYMENT_ORDERS',
        ]);

        $emailAction = Action::new('sendEmail', 'payment_order.action.email', 'fas fa-envelope')
            ->linkToUrl(function (PaymentOrder $paymentOrder) {
                return $this->mailToGenerator->generateContactMailLink($paymentOrder);
            })
            ->setCssClass('btn btn-secondary text-dark');

        //Hide action if no contact emails are associated with department
        $emailAction->displayIf(function (PaymentOrder $paymentOrder) {
            return null !== $this->mailToGenerator->generateContactMailLink($paymentOrder);
        });

        $hhv_action = Action::new('contactHHV', 'payment_order.action.contact_hhv', 'fas fa-comment-dots')
            ->linkToUrl(function (PaymentOrder $paymentOrder) {
                return $this->mailToGenerator->getHHVMailLink($paymentOrder);
            })
            ->setCssClass('btn btn-secondary text-dark');

        $resend_confirmation_action = Action::new('resendConfirmation', 'payment_order.action.resend_confirmation', 'fas fa-redo')
            ->linkToCrudAction('resendConfirmationEmail')
            ->displayIf(function (PaymentOrder $paymentOrder) {
                return $this->isGranted('ROLE_EDIT_PAYMENT_ORDERS') && !$paymentOrder->isConfirmed();
            })
            ->setCssClass('btn btn-secondary text-dark');

        $mathematically_correct_action = Action::new('mathematicallyCorrect', 'payment_order.action.mathematically_correct', 'fas fa-check')
            ->linkToCrudAction('checkMathematicallyCorrect')
            ->displayIf(function (PaymentOrder $paymentOrder) {
                return $this->isGranted('ROLE_PO_MATHEMATICALLY')
                    && $paymentOrder->isConfirmed()
                    && !$paymentOrder->isMathematicallyCorrect();
            })
            ->setCssClass('btn btn-success');

        $factually_correct_action = Action::new('factuallyCorrect', 'payment_order.action.factually_correct', 'fas fa-check')
            ->linkToCrudAction('checkFactuallyCorrect')
            ->displayIf(function (PaymentOrder $paymentOrder) {
                return $this->isGranted('ROLE_PO_FACTUALLY')
                    && $paymentOrder->isConfirmed()
                    && !$paymentOrder->isFactuallyCorrect()
                    && $paymentOrder->isMathematicallyCorrect();
            })
            ->setCssClass('btn btn-success');

        $manual_confirmation = Action::new('manual_confirmation', 'payment_order.action.manual_confirmation', 'fas fa-exclamation-triangle')
            ->setCssClass('btn btn-secondary')
            ->linkToRoute('payment_order_manual_confirm', function (PaymentOrder $paymentOrder) {
                return [
                    'id' => $paymentOrder->getId(),
                ];
            })
            ->displayIf(function (PaymentOrder $paymentOrder) {
                return $this->isGranted('ROLE_MANUAL_CONFIRMATION')
                    && !$paymentOrder->isConfirmed();
            });

        $actions->add(Crud::PAGE_EDIT, $emailAction);
        $actions->add(Crud::PAGE_DETAIL, $emailAction);

        $actions->add(Crud::PAGE_EDIT, $hhv_action);
        $actions->add(Crud::PAGE_DETAIL, $hhv_action);

        $actions->disable(Crud::PAGE_NEW);

        if(!$this->isGranted('ROLE_EDIT_PAYMENT_ORDERS')) {
            $actions->disable('batchDelete');
        }

        $actions->add(Crud::PAGE_DETAIL, $resend_confirmation_action);
        $actions->add(Crud::PAGE_EDIT, $resend_confirmation_action);

        $actions->add(Crud::PAGE_DETAIL, $mathematically_correct_action);
        $actions->add(Crud::PAGE_DETAIL, $factually_correct_action);

        $actions->add(Crud::PAGE_DETAIL, $manual_confirmation);
        $actions->add(Crud::PAGE_EDIT, $manual_confirmation);

        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        //Documents
        $documentsPanel = FormField::addPanel('payment_order.group.documents');
        $printed_form = VichyFileField::new('printed_form_file', 'payment_order.printed_form.label');
        $references = VichyFileField::new('references_file', 'payment_order.references.label');

        //Basic informations
        $infoPanel = FormField::addPanel('payment_order.group.info');
        $id = IntegerField::new('id', 'payment_order.id.label');
        $firstName = TextField::new('first_name', 'payment_order.first_name.label');
        $lastName = TextField::new('last_name', 'payment_order.last_name.label');
        $contact_email = EmailField::new('contact_email', 'payment_order.contact_email.label')
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
            ->setRequired(false);
        $comment = TextEditorField::new('comment', 'payment_order.comment.label')
            ->setRequired(false)
            ->setFormTypeOption('empty_data', '');
        $lastModified = DateTimeField::new('last_modified', 'last_modified');
        $creationDate = DateTimeField::new('creation_date', 'creation_date')
            ->setTemplatePath('admin/field/datetime_overdue_hint.html.twig');
        //$creationDate = TextField::new('creation_date', 'creation_date');

        //Status informations
        $statusPanel = FormField::addPanel('payment_order.group.status');
        $mathematicallyCorrect = BooleanField::new('mathematically_correct', 'payment_order.mathematically_correct.label')
            ->setHelp('payment_order.mathematically_correct.help')
            //Disable fields (and show coloumns as read only tags) if user does not have proper permissions to change
            //factually and mathematically correct status
            ->setFormTypeOption('disabled', !$this->isGranted('ROLE_PO_MATHEMATICALLY'))
            ->renderAsSwitch($this->isGranted('ROLE_PO_MATHEMATICALLY'));
        $exported = BooleanField::new('exported', 'payment_order.exported.label')
            ->setHelp('payment_order.exported.help');
        $factuallyCorrect = BooleanField::new('factually_correct', 'payment_order.factually_correct.label')
            ->setHelp('payment_order.factually_correct.help')
            ->setFormTypeOption('disabled', !$this->isGranted('ROLE_PO_FACTUALLY'))
            ->renderAsSwitch($this->isGranted('ROLE_PO_FACTUALLY'));
        $booking_date = DateTimeField::new('booking_date', 'payment_order.booking_date.label');
        $confirmed_1 = DateTimeField::new('confirm1_timestamp', 'payment_order.confirmed_1.label');
        $confirmed_2 = DateTimeField::new('confirm2_timestamp', 'payment_order.confirmed_2.label');
        $references_exported = BooleanField::new('references_exported', 'payment_order.references_exported.label');

        //Payee informations
        $payeePanel = FormField::addPanel('payment_order.group.receiver');
        $bankInfoAccountOwner = TextField::new('bank_info.account_owner', 'bank_info.account_owner.label');
        $bankInfoStreet = TextField::new('bank_info.street', 'bank_info.street.label');
        $bankInfoZipCode = TextField::new('bank_info.zip_code', 'bank_info.zip_code.label');
        $bankInfoCity = TextField::new('bank_info.city', 'bank_info.city.label');

        //Payee bank account infos
        $bankInfoPanel = FormField::addPanel('payment_order.group.bank_info');
        $bankInfoIban = TextField::new('bank_info.iban', 'bank_info.iban.label');
        $bankInfoBic = TextField::new('bank_info.bic', 'bank_info.bic.label')
            ->setRequired(false)
            ->setFormTypeOption('empty_data', '');
        $bankInfoBankName = TextField::new('bank_info.bank_name', 'bank_info.bank_name.label');
        $bankInfoReference = TextField::new('bank_info.reference', 'bank_info.reference.label')
            ->setRequired(false)
            ->setFormTypeOption('empty_data', '');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $projectName, $department, $amount, $mathematicallyCorrect, $factuallyCorrect, $funding_id_index, $creationDate];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                //Documents section
                $documentsPanel,
                $printed_form,
                $references,
                //Basic informations
                $infoPanel,
                $id,
                $firstName,
                $lastName,
                $contact_email,
                $projectName,
                $department,
                $amount,
                $funding_id,
                $resolution_date,
                $fsr_kom,
                $comment,
                $lastModified,
                $creationDate,
                //Status infos
                $statusPanel,
                $mathematicallyCorrect,
                $exported,
                $factuallyCorrect,
                $booking_date,
                $confirmed_1,
                $confirmed_2,
                //Payee informations
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
            ];
        }

        if (Crud::PAGE_EDIT === $pageName) {
            return [
                //Documents section
                $documentsPanel,
                $printed_form,
                $references,
                //Basic informations
                $infoPanel,
                $firstName,
                $lastName,
                $contact_email,
                $projectName,
                $department,
                $amount,
                $funding_id,
                $resolution_date,
                $fsr_kom,
                $comment,
                //Status infos
                $statusPanel,
                $mathematicallyCorrect,
                $exported,
                $factuallyCorrect,
                $references_exported,
                //Payee informations
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

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
