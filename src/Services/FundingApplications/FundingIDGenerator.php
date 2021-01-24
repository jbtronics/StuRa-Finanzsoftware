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

namespace App\Services\FundingApplications;


use App\Entity\Embeddable\FundingID;
use App\Repository\FundingApplicationRepository;
use Carbon\Carbon;
use Symfony\Component\Lock\LockFactory;

class FundingIDGenerator
{
    private $funding_application_repository;
    private $lock;

    /** @var string[] */
    private $already_assigned_ids = [];

    public function __construct(FundingApplicationRepository $fundingApplicationRepository, LockFactory $lockFactory)
    {
        $this->funding_application_repository = $fundingApplicationRepository;
        $this->lock = $lockFactory->createLock('funding-id-generator');
    }

    /**
     * This function returns the next available funding ID for the given parameters.
     * Every time you call this function with the same params the number is increased.
     * @param  bool  $external_funding
     * @param  \DateTimeInterface|null  $dateTime
     * @return FundingID
     */
    public function getNextAvailableFundingID(bool $external_funding, ?\DateTimeInterface $dateTime = null): FundingID
    {
        if ($dateTime === null) {
            $dateTime = new \DateTime();
        }

        //Try to acquire the lock, so we do not insert the same IDs in multiple processes
        //This is blocking, so we wait until other processes has written their info to DB
        $this->lock->acquire(true);

        $year_part = $this->getBudgetYearPart($dateTime);
        //Determine the next available number from database. Start with 1 if no number was set yet.
        $last_number = $this->funding_application_repository->getHighestFundingIDNumber($external_funding, $year_part) ?? 0;
        $next_number = $last_number + 1;

        //Check if this number was already assigned internally
        $tmp = new FundingID($external_funding, $next_number, $year_part);
        while (in_array((string) $tmp, $this->already_assigned_ids)) {
            $next_number++;
            $tmp = new FundingID($external_funding, $next_number, $year_part);
        }

        //Add string to list of already assigned numbers
        $this->already_assigned_ids[] = (string) $tmp;

        //If we finally found a free number we can return it
        return $tmp;
    }

    /**
     * Release the lock of the funding ID storage.
     * Should be called after flush.
     */
    public function releaseLock(): void
    {
        //Only release it if really a number was created
        if (!empty($this->already_assigned_ids) && $this->lock->isAcquired()) {
            //Clear internal cache
            $this->already_assigned_ids = [];
            //and release lock so other processes can use this
            $this->lock->release();
        }
    }

    /**
     * Returns the budget year part for a given date.
     * E.g. the date 2021-05-01 returns "21/22"
     * @param  \DateTimeInterface|null  $dateTime
     * @return string
     */
    public function getBudgetYearPart(?\DateTimeInterface $dateTime = null): string
    {
        if ($dateTime === null) {
            $dateTime = new \DateTime();
        }

        //Create a comparision date at the first april (then the budget year changes)
        //You can change the budget year here (as long as it is always one year).
        $comparision = new Carbon();
        $comparision->setTime(0,0,0);
        $comparision->setDate($dateTime->format('Y'), 4, 1);


        //If we are after the comparision date we can just use the two digit year
        if ($dateTime >= $comparision) {
            $two_digit_year = $dateTime->format('y');
        } else { //Otherwise we have to use the year from before
            $two_digit_year = ((int) $dateTime->format('y')) - 1;
        }
        //Note that this logic will break for dates before 2000 and and after 2099. But that is nothing we have to worry about now...

        return sprintf("%d/%d", $two_digit_year, $two_digit_year + 1);
    }
}