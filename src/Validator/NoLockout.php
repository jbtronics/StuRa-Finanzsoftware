<?php

namespace App\Validator;

use Doctrine\Common\Annotations\Annotation\Target;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target("CLASS")
 */
class NoLockout extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $message = 'validator.no_lockout';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
