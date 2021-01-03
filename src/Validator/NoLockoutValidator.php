<?php
/*
 * Copyright (C) 2020  Jan Böhmer
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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
        /** @var NoLockout $constraint */

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
        if ($current_user instanceof User && $current_user->getId() === $value->getId() && !in_array(
                'ROLE_EDIT_USER',
                $value->getRoles(),
                true
            )) {
            $this->context->buildViolation($constraint->message)
                    ->addViolation();
        }
    }
}
