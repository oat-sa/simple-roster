<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Responder\SerializerResponder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ErrorHandler implements EventSubscriberInterface
{
    /** @var SerializerResponder */
    private $responder;

    /** @var bool */
    private $debug;

    public function __construct(SerializerResponder $responder, bool $debug)
    {
        $this->responder = $responder;
        $this->debug = $debug;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        // only apply in master requests
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($this->debug) {
            $errorJsonResponse = $this->responder->createErrorJsonResponse($event->getException());

            $event->setResponse($errorJsonResponse);
        }
    }
}
