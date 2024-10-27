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
        'amount',
        'project_name',
        //TODO
    ];

    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();

        // only act on PaymentOrder entities
        if (!$entity instanceof PaymentOrder) {
            return;
        }

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
        $fieldChanges = $entity->getFieldChanges();
        foreach ($fieldsToBeSaved as $field) {
            $fieldChanges->changeField($field, $user);
        }

        //As changes are not tracked anymore, we write out the changes to the database changeset ourselves
        $eventArgs->setNewValue('field_changes', $fieldChanges);
    }
}