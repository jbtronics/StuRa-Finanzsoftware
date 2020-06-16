<?php


namespace App\Entity\Contracts;


interface NamedElementInterface
{

    /**
     * Returns the name of the current element.
     * @return string|null
     */
    public function getName(): ?string;
}