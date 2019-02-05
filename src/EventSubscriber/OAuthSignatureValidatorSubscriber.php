<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Controller\ApiV1\OAuthSignatureValidatedController;
use App\Model\OAuth\Signature;
use App\Repository\InfrastructureRepository;
use App\Security\OAuth\SignatureGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class OAuthSignatureValidatorSubscriber implements EventSubscriberInterface
{
    /** @var InfrastructureRepository */
    private $infrastructureRepository;

    public function __construct(InfrastructureRepository $infrastructureRepository)
    {
        $this->infrastructureRepository = $infrastructureRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event): void
    {
        $controller = $event->getController();

        /*
        * $controller passed can be either a class or a Closure.
        * This is not usual in Symfony but it may happen.
        * If it is a class, it comes in array format
        */
        if (!is_array($controller)) {
            return;
        }

        if ($controller[0] instanceof OAuthSignatureValidatedController) {
            $request = $event->getRequest();

            $infrastructure = $this->infrastructureRepository->getByLtiKey((string)$request->query->get('oauth_consumer_key'));

            if (!$infrastructure) {
                throw new UnauthorizedHttpException('realm="SimpleRoster", oauth_error="consumer key invalid"');
            }

            $signature = new Signature(
                (string)$request->query->get('oauth_body_hash'),
                (string)$request->query->get('oauth_consumer_key'),
                (string)$request->query->get('oauth_nonce'),
                (string)$request->query->get('oauth_signature_method'),
                (string)$request->query->get('oauth_timestamp'),
                (string)$request->query->get('oauth_version')
            );

            $signatureGenerator = new SignatureGenerator(
                $signature,
                $request->getSchemeAndHttpHost() . explode('?', $request->getRequestUri())[0],
                $request->getMethod()
            );

            if ($signatureGenerator->getSignature($infrastructure->getLtiSecret()) !== $request->query->get('oauth_signature')) {
                throw new UnauthorizedHttpException('realm="SimpleRoster", oauth_error="access token invalid"');
            }
        }
    }
}
