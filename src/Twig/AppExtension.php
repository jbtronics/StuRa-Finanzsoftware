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

namespace App\Twig;

use Carbon\Carbon;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class AppExtension extends AbstractExtension
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('formatBytes', $this->formatBytes(...)),
            new TwigFilter('format_datetime_diff', $this->formatDatetimeDiff(...)),
        ];
    }

    public function formatDatetimeDiff(\DateTimeInterface|\Carbon\WeekDay|\Carbon\Month|string|int|float|null $dateTime, $other = null, array $options = [
        'parts' => 2,
    ]): string
    {
        Carbon::setLocale($this->requestStack->getCurrentRequest()->getLocale() ?? 'de');

        return Carbon::parse($dateTime)->diffForHumans($other, $options);
    }

    /**
     * Convert a bytes count into a human-readable form (10000 -> 10K).
     *
     * @param int $precision
     */
    public function formatBytes(int $bytes, $precision = 2): string
    {
        $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$precision}f", $bytes / (1024 ** $factor)).@$size[$factor];
    }
}
