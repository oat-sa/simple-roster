<?php declare(strict_types=1);

namespace App\EventListener;

use App\ApiProblem\ApiProblemResponseGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiProblemExceptionSubscriber implements EventSubscriberInterface
{
    private $apiResponseGenerator;

    public function __construct(ApiProblemResponseGeneratorInterface $apiResponseGenerator)
    {
        $this->apiResponseGenerator = $apiResponseGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException'
        ];
    }

    /**
     * Responsible for setting the proper API Problem JSON response format
     * if an exception happened during a JSON request.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        // only apply in master requests
        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->apiResponseGenerator->supports($event->getRequest(), $event->getException())) {
            return;
        }

        $jsonResponse = $this->apiResponseGenerator
            ->generateResponse($event->getRequest(), $event->getException());

        $event->setResponse($jsonResponse);
    }
}
