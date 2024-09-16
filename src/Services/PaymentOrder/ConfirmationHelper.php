<?php

declare(strict_types=1);


namespace App\Services\PaymentOrder;

use App\Entity\ConfirmationToken;
use App\Entity\Confirmer;
use App\Entity\Embeddable\Confirmation;
use App\Entity\PaymentOrder;
use Doctrine\ORM\EntityManagerInterface;

/**
 * This service offers various helper methods for the confirmation process.
 */
final class ConfirmationHelper
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {

    }

    /**
     * Check if this confirmer has already confirmed this payment order (meaning that he cannot do the second confirmation)
     * @param  Confirmer  $confirmer
     * @param  PaymentOrder  $paymentOrder
     * @return bool
     */
    public function hasAlreadyConfirmed(Confirmer $confirmer, PaymentOrder $paymentOrder): bool
    {
        $confirmation1 = $paymentOrder->getConfirmation1();
        $confirmation2 = $paymentOrder->getConfirmation2();

        return $this->hasAlreadyConfirmedForConfirmation($confirmer, $confirmation1) || $this->hasAlreadyConfirmedForConfirmation($confirmer, $confirmation2);
    }

    private function hasAlreadyConfirmedForConfirmation(Confirmer $confirmer, Confirmation $confirmation): bool
    {
        //If the confirmation is not confirmed, we can skip this check
        if (!$confirmation->isConfirmed()) {
            return false;
        }

        //If the ID of confirmer is the ID of the confirmer, we have a match
        if ($confirmer->getId() === $confirmation->getConfirmerID()) {
            return true;
        }

        //If the confirmer still exists in the database, we know that this was a differnt confirmer
        if ($confirmation->getConfirmerID() !== null && $this->entityManager->find(Confirmer::class, $confirmation->getConfirmerID()) !== null) {
            return false;
        }

        //As last resort, we can compare the confirmation name with the confirmer name
        return $confirmer->getName() === $confirmation->getConfirmerName();
    }

    /**
     * Confirm a payment order with the given confirmation token. This function will not flush the database yet!
     * @param  PaymentOrder  $paymentOrder The confirmation token to use
     * @param  ConfirmationToken  $confirmationToken
     * @param  string|null  $remark An optional remark about the confirmation
     * @return bool
     */
    public function confirm(PaymentOrder $paymentOrder, ConfirmationToken $confirmationToken, ?string $remark = null): void
    {
        if ($paymentOrder->isConfirmed()) {
            throw new \LogicException('The payment order is already confirmed, no further confirmations possible!');
        }
        //Ensure that a user cannot confirm a payment order twice
        if ($this->hasAlreadyConfirmed($confirmationToken->getConfirmer(), $paymentOrder)) {
            throw new \LogicException('The confirmer has already confirmed this payment order!');
        }

        //Determine which confirmation we will fill
        $confirmation = $paymentOrder->getConfirmation1();
        if ($confirmation->isConfirmed()) {
            $confirmation = $paymentOrder->getConfirmation2();
        }

        //Fill the confirmation
        $confirmation->setTimestamp(new \DateTime());
        $confirmation->setConfirmerName($confirmationToken->getConfirmer()->getName());
        $confirmation->setConfirmerID($confirmationToken->getConfirmer()->getId());
        $confirmation->setConfirmationTokenID($confirmationToken);
        $confirmation->setRemark($remark);
    }
}