<?php

declare(strict_types=1);


namespace App\EventSubscriber;

use App\Entity\PaymentOrder;
use App\Services\PaymentOrder\CheckHelper;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This entity listener listens to changes in Payment orders field of "factually_checked" and "mathematically_checked",
 * and updates the "Check" object accordingly.
 */
class PaymentOrderCheckAddInfoSubscriber implements EventSubscriberInterface
{

    public function __construct(private EntityManagerInterface $entityManager, private CheckHelper $checkHelper)
    {

    }

    public function addCheckInfoToPaymentOrder(BeforeEntityUpdatedEvent $event): void
    {
        $paymentOrder = $event->getEntityInstance();

        //Only trigger for PaymentOrder entities
        if (!($paymentOrder instanceof PaymentOrder)) {
            return;
        }

        //Check if the checked state of the "factually_correct" or "mathematically_correct" fields have changed
        $old_data = $this->entityManager->getUnitOfWork()->getOriginalEntityData($paymentOrder);

        //If the "factually_checked" field has changed
        if ($old_data['factually_correct.checked'] !== $paymentOrder->getFactuallyCorrect()->isChecked()) {
            //Perform check if required (was not filled out before
            if ($paymentOrder->getFactuallyCorrect()->isChecked() && $paymentOrder->getFactuallyCorrect()->getTimestamp() === null) {
                //Uncheck the field temporarily to avoid the exception thrown by the checkHelper, if already checked
                $paymentOrder->getFactuallyCorrect()->setChecked(false);
                $this->checkHelper->check($paymentOrder, 'factually_correct');
            }
            //Perform uncheck if required
            elseif (!$paymentOrder->getFactuallyCorrect()->isChecked() && $paymentOrder->getFactuallyCorrect()->getTimestamp() !== null) {
                //Check the field temporarily to avoid the exception thrown by the checkHelper, if already unchecked
                $paymentOrder->getFactuallyCorrect()->setChecked(true);
                $this->checkHelper->uncheck($paymentOrder, 'factually_correct');
            }
        }

        //Do the same for the mathematically_correct field
        if ($old_data['mathematically_correct.checked'] !== $paymentOrder->getMathematicallyCorrect()->isChecked()) {
            //Perform check if required (was not filled out before
            if ($paymentOrder->getMathematicallyCorrect()->isChecked() && $paymentOrder->getMathematicallyCorrect()->getTimestamp() === null) {
                //Uncheck the field temporarily to avoid the exception thrown by the checkHelper, if already checked
                $paymentOrder->getMathematicallyCorrect()->setChecked(false);
                $this->checkHelper->check($paymentOrder, 'mathematically_correct');
            }
            //Perform uncheck if required
            elseif (!$paymentOrder->getMathematicallyCorrect()->isChecked() && $paymentOrder->getMathematicallyCorrect()->getTimestamp() !== null) {
                //Check the field temporarily to avoid the exception thrown by the checkHelper, if already unchecked
                $paymentOrder->getMathematicallyCorrect()->setChecked(true);
                $this->checkHelper->uncheck($paymentOrder, 'mathematically_correct');
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityUpdatedEvent::class => ['addCheckInfoToPaymentOrder']
        ];
    }
}