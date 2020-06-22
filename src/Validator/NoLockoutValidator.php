<?php

namespace App\Validator;

use App\Entity\User;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NoLockoutValidator extends ConstraintValidator
{
    protected $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint \App\Validator\NoLockout */

        if (null === $value || '' === $value) {
            return;
        }

        if (!$constraint instanceof NoLockout) {
            throw new UnexpectedTypeException($value, NoLockout::class);
        }

        if (!$value instanceof User) {
            throw new UnexpectedTypeException($value, User::class);
        }

        $current_user = $this->security->getUser();

        //Perform checks only if the edited user is the one which is logged in
        if ($current_user instanceof User && $current_user->getId() === $value->getId()) {
            if (!in_array('ROLE_EDIT_USER', $value->getRoles())) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        }
    }
}
