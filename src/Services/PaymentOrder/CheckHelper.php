<?php

declare(strict_types=1);


namespace App\Services\PaymentOrder;

use App\Entity\PaymentOrder;
use App\Entity\User;
use App\Event\PaymentOrderCheckFinishedEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * A helper class that provides methods to check/uncheck the factually_correct or mathematically_correct check objects
 * of a PaymentOrder.
 */
final class CheckHelper
{
    public const FACTUALLY_CORRECT = 'factually_correct';
    public const MATHEMATICALLY_CORRECT = 'mathematically_correct';

    public const ALLOWED_FIELDS = [self::FACTUALLY_CORRECT, self::MATHEMATICALLY_CORRECT];

    public function __construct(private readonly Security $security, private readonly EventDispatcherInterface $eventDispatcher)
    {

    }

    /**
     * Check the given field of the given PaymentOrder (factually_correct or mathematically_correct), with data from the
     * currently logged in user. If the field is not allowed, an exception is thrown.
     *
     * If the field is factually_correct, the paymentOrder is also marked as booked.
     *
     * This function does not flush the entity manager yet.
     *
     * @param  PaymentOrder  $paymentOrder
     * @param  string  $field
     * @param  string|null  $remark
     * @return void
     */
    public function check(PaymentOrder $paymentOrder, string $field, ?string $remark = null): void
    {
        if (!in_array($field, self::ALLOWED_FIELDS, true)) {
            throw new \InvalidArgumentException('Invalid field');
        }

        //Check if the current user is allowed to check the field
        if ($field === self::FACTUALLY_CORRECT && !$this->security->isGranted('ROLE_PO_FACTUALLY')) {
            throw new AccessDeniedException('You are not allowed to check this field');
        }
        if ($field === self::MATHEMATICALLY_CORRECT && !$this->security->isGranted('ROLE_PO_MATHEMATICALLY')) {
            throw new AccessDeniedException('You are not allowed to check this field');
        }

        //Retrieve the check object
        $check = $field === self::FACTUALLY_CORRECT ? $paymentOrder->getFactuallyCorrect() : $paymentOrder->getMathematicallyCorrect();

        //Ensure that check is not already checked
        if ($check->isChecked()) {
            throw new \LogicException('This field is already checked. Use uncheck() to uncheck it');
        }

        //Retrieve the user object
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('This method only works with logged in users');
        }

        //Fill in the data from the currently logged in user
        $check->setChecked(true)
            ->setConfirmerID($user->getId())
            ->setConfirmerName($user->getFullName())
            ->setTimestamp(new \DateTime())
            ->setRemark($remark);

        //If the field is factually_correct, mark the paymentOrder as booked
        if ($field === self::FACTUALLY_CORRECT) {
            if ($paymentOrder->getBookingDate() === null) {
                $paymentOrder->setBookingDate(new \DateTime());
            }
        }

        //If both fields are checked now, dispatch the check finished event
        if ($paymentOrder->getFactuallyCorrect()->isChecked() && $paymentOrder->getMathematicallyCorrect()->isChecked()) {
            $this->eventDispatcher->dispatch(new PaymentOrderCheckFinishedEvent($paymentOrder), PaymentOrderCheckFinishedEvent::NAME);
        }
    }

    /**
     * Uncheck the given field of the given PaymentOrder (factually_correct or mathematically_correct) and remove the data
     * from the currently logged in user. If the field is not allowed, an exception is thrown.
     * @param  PaymentOrder  $paymentOrder
     * @param  string  $field
     * @return void
     */
    public function uncheck(PaymentOrder $paymentOrder, string $field): void
    {
        if (!in_array($field, self::ALLOWED_FIELDS, true)) {
            throw new \InvalidArgumentException('Invalid field');
        }

        //Check if the current user is allowed to check the field
        if ($field === self::FACTUALLY_CORRECT && !$this->security->isGranted('ROLE_PO_FACTUALLY')) {
            throw new AccessDeniedException('You are not allowed to check this field');
        }
        if ($field === self::MATHEMATICALLY_CORRECT && !$this->security->isGranted('ROLE_PO_MATHEMATICALLY')) {
            throw new AccessDeniedException('You are not allowed to check this field');
        }

        //Retrieve the check object
        $check = $field === self::FACTUALLY_CORRECT ? $paymentOrder->getFactuallyCorrect() : $paymentOrder->getMathematicallyCorrect();

        //Remove all data from the check object
        $check->setChecked(false)
            ->setConfirmerID(null)
            ->setConfirmerName(null)
            ->setTimestamp(null)
            ->setRemark(null);
    }
}