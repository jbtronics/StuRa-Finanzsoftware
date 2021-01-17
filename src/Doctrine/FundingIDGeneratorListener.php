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

namespace App\Doctrine;


use App\Entity\FundingApplication;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\PrePersist;

class FundingIDGeneratorListener
{
    /** @PrePersist */
    public function prePersistHandler(FundingApplication $fundingApplication, LifecycleEventArgs $event): void
    {
        //TODO
        //For now just insert a dummy value, so we can persist the value properly
        $fundingApplication->setFundingId(uniqid('', true));
    }
}