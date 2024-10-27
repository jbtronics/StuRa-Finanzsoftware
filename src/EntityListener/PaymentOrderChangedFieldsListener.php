<?php

declare(strict_types=1);


namespace App\EntityListener;

use App\Entity\PaymentOrder;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;

#[AsEntityListener]
class PaymentOrderChangedFieldsListener
{
    /**
     * The fields whose changes we want to track
     */
    private const FIELDS_WHITELIST = [
        'submitter_name',
        'submitter_email',
        'department',
        'funding_id',
        'resolution_date',
        'amount',
        'supporting_funding_id',
        'supporting_funding_date',
        'project_name',
        'invoice_number',
        'customer_number',
        'comment',
        'fsr_kom_resolution',

        //Bank infos
        'bank_info.account_owner',
        'bank_info.street',
        'bank_info.zip_code',
        'bank_info.city',
        'bank_info.iban',
        'bank_info.bic',
        'bank_info.bank_name',
        'bank_info.reference',
    ];

    public function preUpdate(PaymentOrder $paymentOrder, PreUpdateEventArgs $eventArgs): void
    {
        $entity = $paymentOrder;

        // get the changed fields
        $changedFields = array_keys($eventArgs->getEntityChangeSet());

        // filter out the fields we are interested in
        $fieldsToBeSaved = array_intersect($changedFields, self::FIELDS_WHITELIST);

        // if no fields are to be saved, we can return early
        if (empty($fieldsToBeSaved)) {
            return;
        }

        //TODO: Determine Username
        $user = 'StuRa Finanzen';

        //Otherwise add the change to the field_changes property for each changed field

        //We need to clone the field changes to get a fresh instance and enforce doctrine to recalculate the changeset
        $fieldChanges = clone $entity->getFieldChanges();

        foreach ($fieldsToBeSaved as $field) {
            $fieldChanges->changeField($field, $user);
        }

        $entity->setFieldChanges($fieldChanges);

        //Let doctrine recalculate the changeset
        $eventArgs->getObjectManager()->getUnitOfWork()->recomputeSingleEntityChangeSet(
            $eventArgs->getObjectManager()->getClassMetadata(PaymentOrder::class),
            $entity
        );

    }
}