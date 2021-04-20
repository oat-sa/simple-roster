<?php

/*
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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Traits;

use LogicException;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Builder\Builder;
use OAT\Library\Lti1p3Core\Security\Jwt\Parser\Parser;
use OAT\Library\Lti1p3Core\Security\Jwt\TokenInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Validator\Validator;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainFactory;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;
use OAT\Library\Lti1p3Core\Util\Generator\IdGeneratorInterface;

/**
 * @fixme
 * This class is copy of https://github.com/oat-sa/lib-lti1p3-core/blob/master/tests/Traits/SecurityTestingTrait.php
 * to prevent autoloader anomalies until this line is fixed:
 * https://github.com/oat-sa/bundle-lti1p3/blob/master/composer.json#L27
 */
trait Lti1p3SecurityTestingTrait
{
    private function createTestKeyChain(
        string $identifier = 'keyChainIdentifier',
        string $keySetName = 'keySetName',
        string $publicKey = null,
        string $privateKey = null,
        string $privateKeyPassPhrase = null,
        string $algorithm = KeyInterface::ALG_RS256
    ): KeyChainInterface {
        return (new KeyChainFactory())->create(
            $identifier,
            $keySetName,
            $publicKey ?? getenv('TEST_KEYS_ROOT_DIR') . '/public.key',
            $privateKey ?? getenv('TEST_KEYS_ROOT_DIR') . '/private.key',
            $privateKeyPassPhrase,
            $algorithm
        );
    }

    private function buildJwt(
        array $headers = [],
        array $claims = [],
        KeyInterface $key = null
    ): TokenInterface {
        $key = $key ?? $this->createTestKeyChain()->getPrivateKey();
        if ($key === null) {
            throw new LogicException('Key cannot be null');
        }

        return (new Builder(null, $this->createTestIdGenerator()))->build($headers, $claims, $key);
    }

    private function parseJwt(string $tokenString): TokenInterface
    {
        return (new Parser())->parse($tokenString);
    }

    private function verifyJwt(TokenInterface $token, KeyInterface $key): bool
    {
        return (new Validator())->validate($token, $key);
    }

    private function createTestClientAccessToken(RegistrationInterface $registration, array $scopes = []): string
    {
        $keyChain = $registration->getPlatformKeyChain();
        if ($keyChain === null) {
            throw new LogicException('Keychain cannot be null');
        }

        $key = $keyChain->getPrivateKey();
        if ($key === null) {
            throw new LogicException('Key cannot be null');
        }

        $accessToken = $this->buildJwt(
            [],
            [
                MessagePayloadInterface::CLAIM_AUD => $registration->getClientId(),
                'scopes' => $scopes,
            ],
            $key
        );

        return $accessToken->toString();
    }

    private function createTestIdGenerator(string $generatedId = null): IdGeneratorInterface
    {
        return new class ($generatedId) implements IdGeneratorInterface {
            /** @var string */
            private $generatedId;

            public function __construct(string $generatedId = null)
            {
                $this->generatedId = $generatedId ?? 'id';
            }

            public function generate(): string
            {
                return $this->generatedId;
            }
        };
    }
}
