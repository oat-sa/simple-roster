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

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResult;
use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResultInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use OAT\Library\Lti1p3Core\User\UserIdentity;
use OAT\SimpleRoster\DataTransferObject\LoginHintDto;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Exception\AssignmentNotFoundException;
use OAT\SimpleRoster\Lti\Extractor\LoginHintExtractor;
use OAT\SimpleRoster\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Throwable;

class OidcUserAuthenticator implements UserAuthenticatorInterface
{
    private LoginHintExtractor $loginHintExtractor;
    private UserRepository $userRepository;
    private LoggerInterface $logger;

    public function __construct(
        LoginHintExtractor $loginHintExtractor,
        UserRepository $userRepository,
        LoggerInterface $logger
    ) {
        $this->loginHintExtractor = $loginHintExtractor;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    /**
     * @throws LtiException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function authenticate(
        RegistrationInterface $registration,
        string $loginHint
    ): UserAuthenticationResultInterface {
        try {
            $loginHintDto = $this->loginHintExtractor->extract($loginHint);
            $user = $this->userRepository->findByUsernameWithAssignments($loginHintDto->getUsername());

            $this->checkLoginHintConsistency($user, $loginHintDto);

            $this->logger->info(
                sprintf('OIDC authentication was successful with login hint %s', $loginHint),
                [
                    'username' => $loginHintDto->getUsername(),
                    'assignmentId' => $loginHintDto->getAssignmentId(),
                ]
            );

            return new UserAuthenticationResult(true, new UserIdentity($user->getUsername()));
        } catch (Throwable $exception) {
            throw new LtiException($exception->getMessage());
        }
    }

    /**
     * @throws AssignmentNotFoundException
     */
    private function checkLoginHintConsistency(User $user, LoginHintDto $loginHintDto): void
    {
        $assignmentFound = false;

        foreach ($user->getAssignments() as $assignment) {
            if ($assignment->getId()->equals($loginHintDto->getAssignmentId())) {
                $assignmentFound = true;

                break;
            }
        }

        if (!$assignmentFound) {
            throw new AssignmentNotFoundException(
                sprintf(
                    'Assignment with ID %s not found for username %s.',
                    $loginHintDto->getAssignmentId(),
                    $loginHintDto->getUsername()
                )
            );
        }
    }
}
