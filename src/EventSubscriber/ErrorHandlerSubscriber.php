<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Responder\SerializerResponder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ErrorHandlerSubscriber implements EventSubscriberInterface
{
    /** @var SerializerResponder */
    private $responder;

    public function __construct(SerializerResponder $responder)
    {
        $this->responder = $responder;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $event->setResponse(
            $this->responder->createErrorJsonResponse($event->getException())
        );
    }
}
