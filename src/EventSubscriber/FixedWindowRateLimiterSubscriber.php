<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class FixedWindowRateLimiterSubscriber implements EventSubscriberInterface
{
    /** @var RateLimiterFactory */
    private $fixedWindowLimiter;

    /** @var string|null */
    private $fixedWindowRoutes;

    public function __construct(RateLimiterFactory $fixedWindowLimiter, array $fixedWindowRoutes)
    {
        $this->fixedWindowLimiter = $fixedWindowLimiter;
        $this->fixedWindowRoutes = $fixedWindowRoutes;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 255],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if (!in_array($request->attributes->get('_route'), $this->fixedWindowRoutes)) {
            return;
        }

        $limiter = $this->fixedWindowLimiter->create($request->getClientIp());
        if (false === $limiter->consume(1)->isAccepted()) {
            $limit = $limiter->consume();

            throw new TooManyRequestsHttpException(
                $limit->getRetryAfter()->getTimestamp(),
                sprintf("Rate Limit Exceeded. Please retry after: %s", $limit->getRetryAfter()->format(DATE_ATOM))
            );
        }
    }
}
