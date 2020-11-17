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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Security\Lti;

use OAT\Library\Lti1p3Core\Security\User\UserAuthenticationResult;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticationResultInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use OAT\Library\Lti1p3Core\User\UserIdentity;
use OAT\SimpleRoster\DataTransferObject\LoginHintDto;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Exception\LineItemNotFoundException;
use OAT\SimpleRoster\Lti\Exception\InvalidGroupException;
use OAT\SimpleRoster\Lti\Extractor\LoginHintExtractor;
use OAT\SimpleRoster\Repository\UserRepository;

class OidcUserAuthenticator implements UserAuthenticatorInterface
{
    public const UNDEFINED_USERNAME = 'Undefined Username';

    /** @var LoginHintExtractor */
    private $loginHintExtractor;

    /** @var UserRepository */
    private $userRepository;

    public function __construct(LoginHintExtractor $loginHintExtractor, UserRepository $userRepository)
    {
        $this->loginHintExtractor = $loginHintExtractor;
        $this->userRepository = $userRepository;
    }

    public function authenticate(string $loginHint): UserAuthenticationResultInterface
    {
        $loginHintDto = $this->loginHintExtractor->extract($loginHint);
        $user = $this->userRepository->findByUsernameWithAssignments($loginHintDto->getUsername());

        $this->checkLoginHintConsistency($user, $loginHintDto);

        return new UserAuthenticationResult(
            true,
            new UserIdentity((string) $user->getUsername())
        );
    }

    private function checkLoginHintConsistency(User $user, LoginHintDto $loginHintDto): void
    {
        if ($user->getGroupId() !== $loginHintDto->getGroupId()) {
            throw new InvalidGroupException('User and group id are not matching.');
        }

        $lineItemFound = false;

        foreach ($user->getAssignments() as $assignment) {
            if ($assignment->getLineItem()->getSlug() === $loginHintDto->getSlug()) {
                $lineItemFound = true;
                break;
            }
        }

        if (!$lineItemFound) {
            throw new LineItemNotFoundException(
                sprintf(
                    'Line Item with slug %s not found for username %s',
                    $loginHintDto->getSlug(),
                    $loginHintDto->getUsername()
                )
            );
        }
    }
}
