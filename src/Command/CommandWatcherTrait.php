<?php declare(strict_types=1);

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
