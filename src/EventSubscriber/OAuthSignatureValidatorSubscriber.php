<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Security\OAuth\OAuthContext;
use App\Repository\InfrastructureRepository;
use App\Security\OAuth\OAuthSignatureValidatedAction;
use App\Security\OAuth\OAuthSigner;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class OAuthSignatureValidatorSubscriber implements EventSubscriberInterface
{
    /** @var InfrastructureRepository */
    private $infrastructureRepository;

    /** @var OAuthSigner */
    private $signer;

    public function __construct(InfrastructureRepository $infrastructureRepository, OAuthSigner $signer)
    {
        $this->infrastructureRepository = $infrastructureRepository;
        $this->signer = $signer;
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
        $action = $event->getController();

        if (!$action instanceof OAuthSignatureValidatedAction) {
            return;
        }

        $request = $event->getRequest();

        $infrastructure = $this->infrastructureRepository->getByLtiKey((string)$request->query->get('oauth_consumer_key'));

        if (!$infrastructure) {
            throw new UnauthorizedHttpException('realm="SimpleRoster", oauth_error="consumer key invalid"');
        }

        $context = new OAuthContext(
            (string)$request->query->get('oauth_body_hash'),
            (string)$request->query->get('oauth_consumer_key'),
            (string)$request->query->get('oauth_nonce'),
            (string)$request->query->get('oauth_signature_method'),
            (string)$request->query->get('oauth_timestamp'),
            (string)$request->query->get('oauth_version')
        );

        $signature = $this->signer->sign(
            $context,
            $request->getSchemeAndHttpHost() . explode('?', $request->getRequestUri())[0],
            $request->getMethod(),
            $infrastructure->getLtiSecret()
        );

        if ($signature !== $request->query->get('oauth_signature')) {
            throw new UnauthorizedHttpException('realm="SimpleRoster", oauth_error="access token invalid"');
        }
    }
}
