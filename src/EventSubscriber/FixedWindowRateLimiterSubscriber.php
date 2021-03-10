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

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class FixedWindowRateLimiterSubscriber implements EventSubscriberInterface
{
    /** @var RateLimiterFactory */
    private $fixedWindowLimiter;

    /** @var string[] */
    private $fixedWindowRoutes;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        RateLimiterFactory $fixedWindowLimiter,
        LoggerInterface $securityLogger,
        array $fixedWindowRoutes
    ) {
        $this->fixedWindowLimiter = $fixedWindowLimiter;
        $this->fixedWindowRoutes = $fixedWindowRoutes;
        $this->logger = $securityLogger;
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

        $clientIp = $request->getClientIp();
        $limiter = $this->fixedWindowLimiter->create($clientIp);

        if ($limiter->consume(1)->isAccepted()) {
            return;
        }
        
        $limit = $limiter->consume();

        $this->logger->warning(
            sprintf('The client with ip: %s, exceeded the limit of requests.', $clientIp),
            [
                'routes' => $this->fixedWindowRoutes,
                'limit' => $limit->getLimit()
            ]
        );

        $retryAfter = $limit->getRetryAfter();
        throw new TooManyRequestsHttpException(
            $retryAfter->getTimestamp(),
            sprintf("Rate Limit Exceeded. Please retry after: %s", $retryAfter->format(DATE_ATOM))
        );
    }
}
