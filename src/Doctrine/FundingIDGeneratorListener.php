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


use App\Entity\Embeddable\FundingID;
use App\Entity\FundingApplication;
use App\Services\FundingApplications\FundingIDGenerator;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\PostPersist;
use Doctrine\ORM\Mapping\PrePersist;

/**
 * This Entity listerner inserts funding IDs when a funding application is created.
 * @package App\Doctrine
 */
class FundingIDGeneratorListener
{
    private $fundingIDGenerator;

    public function __construct(FundingIDGenerator $fundingIDGenerator)
    {
        $this->fundingIDGenerator = $fundingIDGenerator;
    }

    /** @PrePersist */
    public function prePersistHandler(FundingApplication $fundingApplication, LifecycleEventArgs $event): void
    {
        //Insert the funding ID only if no one was set yet
        if ($fundingApplication->getFundingId() === null) {
            $fundingID = $this->fundingIDGenerator->getNextAvailableFundingID(
                $fundingApplication->isExternalFunding(),
                $fundingApplication->getCreationDate()
            );
            $fundingApplication->setFundingId($fundingID);
        }
    }

    /** @PostPersist */
    public function postPersistHandler(FundingApplication $fundingApplication, LifecycleEventArgs $event)
    {
        //Release the acquired lock
        $this->fundingIDGenerator->releaseLock();
    }
}