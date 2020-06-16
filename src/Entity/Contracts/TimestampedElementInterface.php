<?php


namespace App\Entity\Contracts;


interface TimestampedElementInterface
{
    /**
     * Returns the datetime this element was created. Returns null, if it was not persisted yet.
     * @return \DateTime|null
     */
    public function getCreationDate(): ?\DateTime;

    /**
     * Returns the datetime this element was last time modified. Returns null, if it was not persisted yet.
     * @return \DateTime|null
     */
    public function getLastModifiedDate(): ?\DateTime;
}