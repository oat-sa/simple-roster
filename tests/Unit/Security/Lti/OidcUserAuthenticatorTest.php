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

use OAT\SimpleRoster\DataTransferObject\LoginHintDto;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Exception\LineItemNotFoundException;
use OAT\SimpleRoster\Lti\Exception\InvalidGroupException;
use OAT\SimpleRoster\Lti\Extractor\LoginHintExtractor;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Security\Lti\OidcUserAuthenticator;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OidcUserAuthenticatorTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    /** @var OidcUserAuthenticator */
    private $subject;

    /** @var LoginHintExtractor|MockObject */
    private $loginHintExtractor;

    /** @var UserRepository|MockObject */
    private $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->loginHintExtractor = $this->createMock(LoginHintExtractor::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->subject = new OidcUserAuthenticator($this->loginHintExtractor, $this->userRepository);

        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');
    }

    public function testAuthenticate(): void
    {
        $loginHint = 'user1::group_1::lineItemSlug';
        $loginHintDto = new LoginHintDto('user1', 'group_1', 'lineItemSlug');

        $user = $this->getRepository(User::class)->find(1);

        $this->loginHintExtractor
            ->expects($this->once())
            ->method('extract')
            ->willReturn($loginHintDto);

        $this->userRepository
            ->expects($this->once())
            ->method('findByUsernameWithAssignments')
            ->with('user1')
            ->willReturn($user);

        $result = $this->subject->authenticate($loginHint);

        /* @phpstan-ignore-next-line */
        $this->assertSame('user1', $result->getUserIdentity()->getIdentifier());
    }

    public function testShouldThrowInvalidGroupExceptionIfLoginHintHasInvalidGroupId(): void
    {
        $this->expectException(InvalidGroupException::class);
        $this->expectExceptionMessage('User and group id are not matching.');

        $user = $this->getRepository(User::class)->find(1);

        $loginHint = 'user1::invalidGroupId::lineItemSlug';
        $loginHintDto = new LoginHintDto('user1', 'invalidGroupId', 'lineItemSlug');

        $this->userRepository
            ->expects($this->once())
            ->method('findByUsernameWithAssignments')
            ->with('user1')
            ->willReturn($user);

        $this->loginHintExtractor
            ->expects($this->once())
            ->method('extract')
            ->with($loginHint)
            ->willReturn($loginHintDto);

        $this->subject->authenticate($loginHint);
    }

    public function testShouldThrowLineItemNotFoundExceptionIfNoLineItemsAreFoundWithSpecifiedSlug(): void
    {
        $this->expectException(LineItemNotFoundException::class);
        $this->expectExceptionMessage('Line Item with slug invalidSlug not found for username user1');

        $user = $this->getRepository(User::class)->find(1);

        $loginHint = 'user1::invalidGroupId::invalidSlug';
        $loginHintDto = new LoginHintDto('user1', 'group_1', 'invalidSlug');

        $this->userRepository
            ->expects($this->once())
            ->method('findByUsernameWithAssignments')
            ->with('user1')
            ->willReturn($user);

        $this->loginHintExtractor
            ->expects($this->once())
            ->method('extract')
            ->with($loginHint)
            ->willReturn($loginHintDto);

        $this->subject->authenticate($loginHint);
    }
}
