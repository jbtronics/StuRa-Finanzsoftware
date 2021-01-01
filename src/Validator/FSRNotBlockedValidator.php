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
        /** @var FSRNotBlocked $constraint */

        if (!$constraint instanceof FSRNotBlocked) {
            throw new UnexpectedTypeException($constraint, FSRNotBlocked::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof Department) {
            throw new UnexpectedTypeException($value, Department::class);
        }

        if ($value->isBlocked()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ fsr }}', $value->getName())
                ->setParameter('{{ email }}', $this->fsb_email)
                ->addViolation();
        }
    }
}
