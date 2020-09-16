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

use App\Repository\InfrastructureRepository;
use App\Security\OAuth\OAuthContext;
use App\Security\OAuth\OAuthSignatureValidatedActionInterface;
use App\Security\OAuth\OAuthSigner;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class OAuthSignatureValidationSubscriber implements EventSubscriberInterface
{
    public const AUTH_REALM = 'SimpleRoster';

    /** @var InfrastructureRepository */
    private $repository;

    /** @var OAuthSigner */
    private $signer;

    /** @var string */
    private $ltiSecret;

    public function __construct(InfrastructureRepository $repository, OAuthSigner $signer, string $ltiSecret)
    {
        $this->repository = $repository;
        $this->signer = $signer;
        $this->ltiSecret = $ltiSecret;
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
    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->getController() instanceof OAuthSignatureValidatedActionInterface) {
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
            $this->ltiSecret
        );

        if ($signature !== $request->query->get('oauth_signature')) {
            throw new UnauthorizedHttpException(
                sprintf('realm="%s", oauth_error="access token invalid"', static::AUTH_REALM)
            );
        }
    }
}
