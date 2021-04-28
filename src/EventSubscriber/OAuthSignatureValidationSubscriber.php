<?php

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

declare(strict_types=1);

namespace OAT\SimpleRoster\EventSubscriber;

use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use OAT\SimpleRoster\Security\OAuth\OAuthContext;
use OAT\SimpleRoster\Security\OAuth\OAuthSignatureValidatedActionInterface;
use OAT\SimpleRoster\Security\OAuth\OAuthSigner;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class OAuthSignatureValidationSubscriber implements EventSubscriberInterface
{
    public const AUTH_REALM = 'SimpleRoster';

    /** @var LtiInstanceRepository */
    private $repository;

    /** @var OAuthSigner */
    private $signer;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(LtiInstanceRepository $repository, OAuthSigner $signer, LoggerInterface $securityLogger)
    {
        $this->repository = $repository;
        $this->signer = $signer;
        $this->logger = $securityLogger;
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

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->getController() instanceof OAuthSignatureValidatedActionInterface) {
            return;
        }

        $request = $event->getRequest();

        $context = new OAuthContext(
            (string)$request->query->get('oauth_body_hash'),
            (string)$request->query->get('oauth_consumer_key'),
            (string)$request->query->get('oauth_nonce'),
            (string)$request->query->get('oauth_signature_method'),
            (string)$request->query->get('oauth_timestamp'),
            (string)$request->query->get('oauth_version')
        );

        $ltiKeyToValidate = (string)$request->query->get('oauth_consumer_key');
        $possibleLtiInstances = $this->repository
            ->findAllAsCollection()
            ->filterByLtiKey($ltiKeyToValidate);

        if ($possibleLtiInstances->isEmpty()) {
            $this->logger->error(
                sprintf(
                    "Invalid OAuth consumer key received, LTI instance with LTI key = '%s' cannot be found.",
                    $ltiKeyToValidate
                )
            );

            throw new UnauthorizedHttpException(
                sprintf('realm="%s", oauth_error="consumer key invalid"', self::AUTH_REALM)
            );
        }

        $signatureToValidate = $request->query->get('oauth_signature');
        foreach ($possibleLtiInstances as $ltiInstance) {
            $signature = $this->signer->sign(
                $context,
                $request->getSchemeAndHttpHost() . explode('?', $request->getRequestUri())[0],
                $request->getMethod(),
                $ltiInstance->getLtiSecret()
            );

            if ($signature === $signatureToValidate) {
                $this->logger->info('Successful OAuth signature validation.', ['ltiInstance' => $ltiInstance]);

                return;
            }
        }

        $this->logger->error(
            'Failed OAuth signature validation.',
            [
                'context' => $context,
                'signature' => $signatureToValidate,
            ]
        );

        throw new UnauthorizedHttpException(
            sprintf('realm="%s", oauth_error="access token invalid"', self::AUTH_REALM)
        );
    }
}
