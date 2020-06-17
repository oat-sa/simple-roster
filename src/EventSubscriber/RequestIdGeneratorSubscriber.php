<?php

declare(strict_types=1);

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

namespace App\EventSubscriber;

use App\Request\RequestIdStorage;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestIdGeneratorSubscriber implements EventSubscriberInterface
{
    public const CLOUDFRONT_REQUEST_ID_HEADER = 'x-edge-request-id';

    /** @var UuidFactoryInterface */
    private $uuidFactory;

    /** @var RequestIdStorage */
    private $requestIdStorage;

    public function __construct(UuidFactoryInterface $uuidFactory, RequestIdStorage $requestIdStorage)
    {
        $this->uuidFactory = $uuidFactory;
        $this->requestIdStorage = $requestIdStorage;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 255],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestId = $request->headers->get(self::CLOUDFRONT_REQUEST_ID_HEADER);

        if (!$requestId) {
            /** @var Uuid $requestId */
            $requestId = $this->uuidFactory->uuid4();
        }

        $this->requestIdStorage->setRequestId((string)$requestId);
        $request->attributes->set('requestId', $this->requestIdStorage->getRequestId());
    }
}
