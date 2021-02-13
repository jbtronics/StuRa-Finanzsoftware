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

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class SetTimezoneSubscriber implements EventSubscriberInterface
{
    private $default_timezone;

    public function __construct(string $timezone)
    {
        $this->default_timezone = $timezone;
    }

    public function setTimeZone(ControllerEvent $event): void
    {
        date_default_timezone_set($this->default_timezone);
    }

    public static function getSubscribedEvents()
    {
        //Set the timezone shortly before executing the controller
        return [
            KernelEvents::CONTROLLER => 'setTimeZone',
        ];
    }
}
