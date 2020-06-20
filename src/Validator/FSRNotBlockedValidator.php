<?php

namespace App\Validator;

use App\Entity\Department;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class FSRNotBlockedValidator extends ConstraintValidator
{
    private $fsb_email;

    public function __construct(string $fsb_email)
    {
        $this->fsb_email = $fsb_email;
    }

    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint \App\Validator\FSRNotBlocked */

        if (!$constraint instanceof FSRNotBlocked) {
            throw new UnexpectedTypeException($constraint, FSRNotBlocked::class);
        }

        if (!$value instanceof Department) {
            throw new UnexpectedTypeException($value, Department::class);
        }

        if (null === $value || '' === $value) {
            return;
        }


        if ($value->isBlocked()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ fsr }}', $value->getName())
                ->setParameter('{{ email }}', $this->fsb_email)
                ->addViolation();
        }
    }
}
