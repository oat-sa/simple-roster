<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Security\OAuth\OAuthContext;
use App\Repository\InfrastructureRepository;
use App\Security\OAuth\OAuthSignatureValidatedActionInterface;
use App\Security\OAuth\OAuthSigner;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class OAuthSignatureValidationSubscriber implements EventSubscriberInterface
{
    public const AUTH_REALM = 'SimpleRoster';

    /** @var InfrastructureRepository */
    private $repository;

    /** @var OAuthSigner */
    private $signer;

    public function __construct(InfrastructureRepository $repository, OAuthSigner $signer)
    {
        $this->repository = $repository;
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
     * @throws NonUniqueResultException
     */
    public function onKernelController(FilterControllerEvent $event): void
    {
        $action = $event->getController();

        if (!$action instanceof OAuthSignatureValidatedActionInterface) {
            return;
        }

        $request = $event->getRequest();

        $infrastructure = $this->repository->getByLtiKey(
            (string)$request->query->get('oauth_consumer_key')
        );

        if (!$infrastructure) {
            throw new UnauthorizedHttpException(
                sprintf('realm="%s", oauth_error="consumer key invalid"', static::AUTH_REALM)
            );
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
            throw new UnauthorizedHttpException(
                sprintf('realm="%s", oauth_error="access token invalid"', static::AUTH_REALM)
            );
        }
    }
}
