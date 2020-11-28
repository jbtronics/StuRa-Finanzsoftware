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
use App\Services\EmailConfirmation\ConfirmationEmailSender;
use App\Services\PaymentEmailMailToGenerator;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Date;

class PaymentOrderCrudController extends AbstractCrudController
{
    private $mailToGenerator;
    private $dashboardControllerRegistry;
    private $confirmationEmailSender;
    private $request;
    private $entityManager;

    public function __construct(PaymentEmailMailToGenerator $mailToGenerator,
        DashboardControllerRegistry $dashboardControllerRegistry, EntityManagerInterface $entityManager,
        ConfirmationEmailSender $confirmationEmailSender, RequestStack $requestStack)
    {
        $this->mailToGenerator = $mailToGenerator;
        $this->dashboardControllerRegistry = $dashboardControllerRegistry;
        $this->confirmationEmailSender = $confirmationEmailSender;

        $this->request = $requestStack->getCurrentRequest();
        $this->entityManager = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return PaymentOrder::class;
    }

    public function export(array $ids, AdminContext $context): Response
    {
        //We must add an eaContext Parameter or we will run into an error...
        $context_id = $this->dashboardControllerRegistry->getContextIdByControllerFqcn($context->getDashboardControllerFqcn());

        return $this->redirectToRoute('payment_order_export', [
            'eaContext' => $context_id,
            'ids' => implode(",", $ids)
        ]);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('payment_order.label')
            ->setEntityLabelInPlural('payment_order.labelp')
            ->setSearchFields(['id', 'first_name', 'last_name', 'project_name', 'amount', 'comment', 'bank_info.account_owner', 'bank_info.street', 'bank_info.zip_code', 'bank_info.city', 'bank_info.iban', 'bank_info.bic', 'bank_info.bank_name', 'bank_info.reference']);
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
            ->add(DateTimeFilter::new('creation_date', 'creation_date'))
            ->add(DateTimeFilter::new('last_modified', 'last_modified'));
    }

    public function resendConfirmationEmail(AdminContext $context): Response
    {
        $payment_order = $context->getEntity()->getInstance();

        $this->confirmationEmailSender->resendConfirmations($payment_order);

        $this->addFlash('success', 'payment_order.action.resend_confirmation.success');

        return $this->redirect($context->getReferrer() ?? '/admin');
    }

    public function checkMathematicallyCorrect(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PO_MATHEMATICALLY');

        /** @var PaymentOrder $payment_order */
        $payment_order = $context->getEntity()->getInstance();
        $payment_order->setMathematicallyCorrect(true);
        $this->entityManager->flush();
        $this->addFlash('success', 'payment_order.action.mathematically_correct.success');
        return $this->redirect($context->getReferrer() ?? '/admin');
    }

    public function checkFactuallyCorrect(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PO_FACTUALLY');

        /** @var PaymentOrder $payment_order */
        $payment_order = $context->getEntity()->getInstance();
        $payment_order->setFactuallyCorrect(true);
        $this->entityManager->flush();
        $this->addFlash('success', 'payment_order.action.factually_correct.success');
        return $this->redirect($context->getReferrer() ?? '/admin');
    }

    public function configureActions(Actions $actions): Actions
    {
        // Button with text and icon
        $actions->add(Crud::PAGE_INDEX, Action::new('Export')
            ->createAsBatchAction()
            ->linkToCrudAction('export')
            ->addCssClass('btn btn-primary')
            ->setIcon('fas fa-file-export')
        );

        $actions->setPermissions([
                                     Action::INDEX => 'ROLE_SHOW_PAYMENT_ORDERS',
                                     Action::DETAIL => 'ROLE_SHOW_PAYMENT_ORDERS',
                                     Action::EDIT => 'ROLE_EDIT_PAYMENT_ORDERS',
                                     Action::DELETE => 'ROLE_EDIT_PAYMENT_ORDERS',
                                     Action::NEW => 'ROLE_EDIT_PAYMENT_ORDERS',
                                 ]);

        $emailAction = Action::new('sendEmail', 'payment_order.action.email', 'fas fa-envelope')
            ->linkToUrl(function(PaymentOrder $paymentOrder) {
                return $this->mailToGenerator->generateMailToHref($paymentOrder);
            })
            ->setCssClass('text-dark');

        //Hide action if no contact emails are associated with department
        $emailAction->displayIf(function(PaymentOrder $paymentOrder) {
            return $this->mailToGenerator->generateMailToHref($paymentOrder) !== null;
        });

        $hhv_action = Action::new('contactHHV', 'payment_order.action.contact_hhv', 'fas fa-comment-dots')
            ->linkToUrl(function(PaymentOrder $paymentOrder) {
                return $this->mailToGenerator->getHHVMailLink($paymentOrder);
            })
            ->setCssClass('mr-2 text-dark');

        $resend_confirmation_action = Action::new('resendConfirmation', 'payment_order.action.resend_confirmation', 'fas fa-redo')
            ->linkToCrudAction('resendConfirmationEmail')
            ->displayIf(function (PaymentOrder $paymentOrder) {
                return $paymentOrder->getConfirm2Timestamp() === null || $paymentOrder->getConfirm1Timestamp() === null;
            })
            ->setCssClass('mr-2 text-dark');

        $mathematically_correct_action = Action::new('mathematicallyCorrect', 'payment_order.action.mathematically_correct', 'fas fa-check')
            ->linkToCrudAction('checkMathematicallyCorrect')
            ->displayIf(function (PaymentOrder $paymentOrder) {
                return $this->isGranted('ROLE_PO_MATHEMATICALLY')
                    && $paymentOrder->isConfirmed()
                    && !$paymentOrder->isMathematicallyCorrect();
            })
            ->setCssClass('mr-2 btn btn-success');

        $factually_correct_action = Action::new('factuallyCorrect', 'payment_order.action.factually_correct', 'fas fa-check')
            ->linkToCrudAction('checkFactuallyCorrect')
            ->displayIf(function (PaymentOrder $paymentOrder) {
                return $this->isGranted('ROLE_PO_FACTUALLY')
                    && $paymentOrder->isConfirmed()
                    && !$paymentOrder->isFactuallyCorrect()
                    && $paymentOrder->isMathematicallyCorrect();
            })
            ->setCssClass('mr-2 btn btn-success');

        $actions->add(Crud::PAGE_EDIT, $emailAction);
        $actions->add(Crud::PAGE_DETAIL, $emailAction);

        $actions->add(Crud::PAGE_EDIT, $hhv_action);
        $actions->add(Crud::PAGE_DETAIL, $hhv_action);

        $actions->disable(Crud::PAGE_NEW);


        $actions->add(Crud::PAGE_DETAIL, $resend_confirmation_action);
        $actions->add(Crud::PAGE_EDIT, $resend_confirmation_action);

        $actions->add(Crud::PAGE_DETAIL, $mathematically_correct_action);
        $actions->add(Crud::PAGE_DETAIL, $factually_correct_action);


        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        $panel1 = FormField::addPanel('payment_order.group.info');
        $firstName = TextField::new('first_name', 'payment_order.first_name.label');
        $lastName = TextField::new('last_name', 'payment_order.last_name.label');
        $department = AssociationField::new('department', 'payment_order.department.label')->setFormTypeOption('attr', ['data-widget' => "select2"]);
        $amount = MoneyField::new('amount', 'payment_order.amount.label')->setCurrency('EUR')->setStoredAsCents(true);
        $projectName = TextField::new('project_name', 'payment_order.project_name.label');
        $comment = TextEditorField::new('comment', 'payment_order.comment.label')->setRequired(false)->setFormTypeOption('empty_data', '');
        $panel2 = FormField::addPanel('payment_order.group.status');
        $mathematicallyCorrect = BooleanField::new('mathematically_correct', 'payment_order.mathematically_correct.label')->setHelp('payment_order.mathematically_correct.help');
        $exported = BooleanField::new('exported', 'payment_order.exported.label')->setHelp('payment_order.exported.help');
        $factuallyCorrect = BooleanField::new('factually_correct', 'payment_order.factually_correct.label')->setHelp('payment_order.factually_correct.help');
        $panel3 = FormField::addPanel('payment_order.group.receiver');
        $bankInfoAccountOwner = TextField::new('bank_info.account_owner', 'bank_info.account_owner.label');
        $bankInfoStreet = TextField::new('bank_info.street', 'bank_info.street.label');
        $bankInfoZipCode = TextField::new('bank_info.zip_code', 'bank_info.zip_code.label');
        $bankInfoCity = TextField::new('bank_info.city', 'bank_info.city.label');
        $panel4 = FormField::addPanel('payment_order.group.bank_info');
        $bankInfoIban = TextField::new('bank_info.iban', 'bank_info.iban.label');
        $bankInfoBic = TextField::new('bank_info.bic', 'bank_info.bic.label');
        $bankInfoBankName = TextField::new('bank_info.bank_name', 'bank_info.bank_name.label');
        $bankInfoReference = TextField::new('bank_info.reference', 'bank_info.reference.label')->setRequired(false)->setFormTypeOption('empty_data', '');
        $id = IntegerField::new('id', 'payment_order.id.label');
        $lastModified = DateTimeField::new('last_modified', 'last_modified');
        $creationDate = DateTimeField::new('creation_date', 'creation_date');
        $departmentName = TextareaField::new('department.name', 'payment_order.department.label');

        $confirmed_1 = DateTimeField::new('confirm1_timestamp', 'payment_order.confirmed_1.label');
        $confirmed_2 = DateTimeField::new('confirm2_timestamp', 'payment_order.confirmed_2.label');

        $funding_id = TextField::new('funding_id', 'payment_order.funding_id.label')->setRequired(false)->setFormTypeOption('empty_data', '');

        //Disable fields (and show coloumns as read only tags) if user does not have proper permissions to change
        //factually and mathematically correct status
        $mathematicallyCorrect->setFormTypeOption('disabled', !$this->isGranted('ROLE_PO_MATHEMATICALLY'));
        $mathematicallyCorrect->renderAsSwitch($this->isGranted('ROLE_PO_MATHEMATICALLY'));

        $factuallyCorrect->setFormTypeOption('disabled', !$this->isGranted('ROLE_PO_FACTUALLY'));
        $factuallyCorrect->renderAsSwitch($this->isGranted('ROLE_PO_FACTUALLY'));

        $panel_documents = FormField::addPanel('payment_order.group.documents');
        $printed_form = VichyFileField::new('printed_form_file', 'payment_order.printed_form.label');
        $references = VichyFileField::new('references_file', 'payment_order.references.label');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $projectName, $departmentName, $amount, $mathematicallyCorrect, $factuallyCorrect, $creationDate];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$panel_documents, $printed_form, $references, $panel1, $id, $firstName, $lastName, $projectName, $department, $amount, $funding_id, $panel2, $confirmed_1, $confirmed_2, $mathematicallyCorrect, $exported, $factuallyCorrect, $comment, $panel3, $bankInfoAccountOwner, $bankInfoStreet, $bankInfoZipCode, $bankInfoCity, $panel4, $bankInfoIban, $bankInfoBic, $bankInfoBankName, $bankInfoReference, $lastModified, $creationDate];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$panel_documents, $printed_form, $references, $panel1, $firstName, $lastName, $department, $amount, $projectName, $funding_id, $panel2, $mathematicallyCorrect, $factuallyCorrect, $comment, $panel3, $bankInfoAccountOwner, $bankInfoStreet, $bankInfoZipCode, $bankInfoCity, $panel4, $bankInfoIban, $bankInfoBic, $bankInfoBankName, $bankInfoReference];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$panel_documents, $printed_form, $references, $panel1, $references, $firstName, $lastName, $department, $amount, $projectName, $funding_id,  $panel2, $mathematicallyCorrect, $exported, $factuallyCorrect, $comment, $panel3, $bankInfoAccountOwner, $bankInfoStreet, $bankInfoZipCode, $bankInfoCity, $panel4, $bankInfoIban, $bankInfoBic, $bankInfoBankName, $bankInfoReference];
        }

        throw new \RuntimeException("It should not be possible to reach this point...");
    }
}
