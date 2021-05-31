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

namespace OAT\SimpleRoster\Tests\Unit\Security\Lti;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Registration\Registration;
use OAT\Library\Lti1p3Core\User\UserIdentityInterface;
use OAT\SimpleRoster\DataTransferObject\LoginHintDto;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Lti\Extractor\LoginHintExtractor;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Security\Lti\OidcUserAuthenticator;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OidcUserAuthenticatorTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    private OidcUserAuthenticator $subject;

    /** @var LoginHintExtractor|MockObject */
    private $loginHintExtractor;

    /** @var UserRepository|MockObject */
    private $userRepository;

    /** @var LoggerInterface|MockObject */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->loginHintExtractor = $this->createMock(LoginHintExtractor::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new OidcUserAuthenticator(
            $this->loginHintExtractor,
            $this->userRepository,
            $this->logger,
        );

        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');
    }

    public function testAuthenticateSuccessfully(): void
    {
        $loginHint = 'user1::1';
        $loginHintDto = new LoginHintDto('user1', 1);

        $user = $this->getRepository(User::class)->find(1);

        $this->loginHintExtractor
            ->expects(self::once())
            ->method('extract')
            ->willReturn($loginHintDto);

        $this->userRepository
            ->expects(self::once())
            ->method('findByUsernameWithAssignments')
            ->with('user1')
            ->willReturn($user);

        $this->logger
            ->expects(self::once())
            ->method('info')
            ->with(
                'OIDC authentication was successful with login hint user1::1',
                [
                    'username' => $loginHintDto->getUsername(),
                    'assignmentId' => $loginHintDto->getAssignmentId(),
                ]
            );

        $result = $this->subject->authenticate($this->createMock(Registration::class), $loginHint);
        $userIdentity = $result->getUserIdentity();
        $userIdentifier = null !== $userIdentity ? $userIdentity->getIdentifier() : null;

        self::assertSame('user1', $userIdentifier);
        self::assertTrue($result->isSuccess());
    }

    public function testAuthenticateShouldFailIfAssignmentIsNotFound(): void
    {
        $this->expectException(LtiException::class);
        $this->expectExceptionMessage('Assignment with ID 2 not found for username user1.');

        $user = $this->getRepository(User::class)->find(1);

        $loginHint = 'user1::2';
        $loginHintDto = new LoginHintDto('user1', 2);

        $this->userRepository
            ->expects(self::once())
            ->method('findByUsernameWithAssignments')
            ->with('user1')
            ->willReturn($user);

        $this->loginHintExtractor
            ->expects(self::once())
            ->method('extract')
            ->with($loginHint)
            ->willReturn($loginHintDto);

        $this->logger
            ->expects(self::never())
            ->method('info');

        $this->subject->authenticate($this->createMock(Registration::class), $loginHint);
    }
}
