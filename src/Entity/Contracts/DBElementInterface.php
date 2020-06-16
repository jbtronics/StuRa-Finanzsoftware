<?php


namespace App\Entity\Contracts;


interface DBElementInterface
{
    /**
     * Returns the internal ID of this element in the DB.
     * Returns null if entity was not persisted yet.
     * @return int|null
     */
    public function getId(): ?int;
}