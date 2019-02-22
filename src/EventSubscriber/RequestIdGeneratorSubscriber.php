<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Request\RequestIdGenerator;
use App\Request\RequestIdStorage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestIdGeneratorSubscriber implements EventSubscriberInterface
{
    public const CLOUDFRONT_REQUEST_ID_HEADER = 'x-edge-request-id';

    /** @var RequestIdGenerator */
    private $requestIdGenerator;

    /** @var RequestIdStorage */
    private $requestIdStorage;

    public function __construct(RequestIdGenerator $requestIdGenerator, RequestIdStorage $requestIdStorage)
    {
        $this->requestIdGenerator = $requestIdGenerator;
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
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestId = $request->headers->get(self::CLOUDFRONT_REQUEST_ID_HEADER)
            ?? $this->requestIdGenerator->generate();

        $request->attributes->set('requestId', $requestId);
        $this->requestIdStorage->setRequestId($requestId);
    }
}
