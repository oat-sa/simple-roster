<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Request\RequestIdStorage;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
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

    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        $requestId = $request->headers->get(self::CLOUDFRONT_REQUEST_ID_HEADER);

        if (!$requestId) {
            /** @var Uuid $requestId */
            $requestId = $this->uuidFactory->uuid4();
        }

        $request->attributes->set('requestId', (string)$requestId);
        $this->requestIdStorage->setRequestId((string)$requestId);
    }
}
