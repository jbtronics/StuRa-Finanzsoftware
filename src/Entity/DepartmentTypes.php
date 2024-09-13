<?php

declare(strict_types=1);


namespace App\Entity;

/**
 * Represents the different types of departments in the system
 */
enum DepartmentTypes: string
{
    /** A "Fachschaftsrat" */
    case FSR = 'fsr';

    /** A "referat" or "Arbeitskreis" */
    case SECTION = 'section';

    /** Verwaltungsstrukturen */
    case ADMINISTRATIVE = 'misc';


    /**
     * @return int The minimum number of confirmers required for this department type.
     * (If a department has more confirmers, two will be required to confirm a payment order)
     */
    public function getMinimumRequiredConfirmers(): int
    {
        return match ($this) {
            self::FSR => 2,
            self::SECTION => 1,
            self::ADMINISTRATIVE => 1,
        };
    }
}