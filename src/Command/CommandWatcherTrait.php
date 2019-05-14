<?php declare(strict_types=1);
/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

namespace App\Command;

use DateInterval;
use DateTime;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

trait CommandWatcherTrait
{
    /** @var Stopwatch|null */
    private $watcher;

    private function startWatch(string $name, string $category): Stopwatch
    {
        if ($this->watcher === null) {
            $this->watcher = new Stopwatch();
        }

        $this->watcher->start($name, $category);

        return $this->watcher;
    }

    private function stopWatch(string $name): string
    {
        return $this->renderStopWatchEvent($this->watcher->stop($name));
    }

    private function renderStopWatchEvent(StopwatchEvent $event): string
    {
        return sprintf(
            'memory: %s - duration: %s',
            $this->formatMemory($event->getMemory()),
            $this->formatDurationInMs($event->getDuration())
        );
    }

    private function formatMemory(int $memory, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($memory, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= 1024 ** $pow;

        return round($bytes, $precision) . $units[$pow];
    }

    private function formatDurationInMs(int $durationInMs): string
    {
        $seconds = floor($durationInMs / 1000);
        $ms = $durationInMs - $seconds * 1000;

        $datetime = new DateTime();
        $datetime->add(new DateInterval('PT' . $seconds . 'S'));
        $di = $datetime->diff(new DateTime());

        return ltrim($di->format('%Dd %Hh %im %ss ' . str_pad((string)$ms, 2, '0', STR_PAD_LEFT) . 'ms'), '0 dhms');
    }
}
