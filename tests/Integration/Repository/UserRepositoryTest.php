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

namespace OAT\SimpleRoster\Tests\Integration\Repository;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityNotFoundException;
use InvalidArgumentException;
use OAT\SimpleRoster\DataTransferObject\UserDto;
use OAT\SimpleRoster\DataTransferObject\UserDtoCollection;
use OAT\SimpleRoster\Exception\InvalidUsernameException;
use OAT\SimpleRoster\Generator\UserCacheIdGenerator;
use OAT\SimpleRoster\Repository\Criteria\FindUserCriteria;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\UuidV6;

class UserRepositoryTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    /** @var UserRepository */
    private $subject;

    /** @var Cache */
    private $doctrineResultCacheImplementation;

    /** @var UserCacheIdGenerator */
    private $userCacheIdGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();

        $this->subject = self::$container->get(UserRepository::class);
        $this->doctrineResultCacheImplementation = self::$container->get('doctrine.orm.default_result_cache');
        $this->userCacheIdGenerator = self::$container->get(UserCacheIdGenerator::class);
    }

    public function testItCanGetUserWithAssignmentsByUsername(): void
    {
        $this->loadFixtureByFilename('100usersWithAssignments.yml');
        $user = $this->subject->findByUsernameWithAssignments('user_1');

        self::assertSame('user_1', $user->getUsername());
        self::assertCount(1, $user->getAssignments());
    }

    public function testItUsesResultCacheImplementationForGettingTheUserWithAssignments(): void
    {
        $this->loadFixtureByFilename('100usersWithAssignments.yml');

        $username = 'user_1';
        $expectedResultCacheId = $this->userCacheIdGenerator->generate($username);

        self::assertFalse($this->doctrineResultCacheImplementation->contains($expectedResultCacheId));

        $this->subject->findByUsernameWithAssignments($username);

        self::assertTrue($this->doctrineResultCacheImplementation->contains($expectedResultCacheId));
    }

    public function testItThrowsExceptionIfWeTryToGetUserWithAssignmentsWithEmptyUsername(): void
    {
        $this->expectException(InvalidUsernameException::class);
        $this->expectExceptionMessage('Empty username received.');

        $this->subject->findByUsernameWithAssignments('');
    }

    public function testItThrowsExceptionIfUserCannotBeFound(): void
    {
        $this->expectException(EntityNotFoundException::class);

        $this->subject->findByUsernameWithAssignments('nonExistingUser');
    }

    public function testItThrowsExceptionIfInvalidLimitProvided(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid 'limit' parameter received.");

        $this->subject->findAllUsernamesByCriteriaPaged(0, null);
    }

    /**
     * @dataProvider provideLimits
     */
    public function testItCanFindAllUsernamesPaged(int $limit): void
    {
        $this->loadFixtureByFilename('100usersWithAssignments.yml');

        $lastUserId = null;
        $userIdIndex = 0;
        do {
            $resultSet = $this->subject->findAllUsernamesByCriteriaPaged($limit, $lastUserId);

            self::assertLessThanOrEqual($limit, count($resultSet));

            foreach ($resultSet as $username) {
                $userIdIndex++;
                self::assertSame(sprintf('user_%d', $userIdIndex), $username);
            }

            $lastUserId = $resultSet->getLastUserId();
        } while ($resultSet->hasMore());

        self::assertSame(100, $userIdIndex);
    }

    public function testItCanCountUsers(): void
    {
        $this->loadFixtureByFilename('100usersWithAssignments.yml');

        self::assertSame(100, $this->subject->countByCriteria());
    }

    public function testItCanCountUsersByUsernameCriteria(): void
    {
        $this->loadFixtureByFilename('100usersWithAssignments.yml');

        $criteria = (new FindUserCriteria())
            ->addUsernameCriterion('user_1', 'user_10', 'user_73', 'user_88');

        self::assertSame(4, $this->subject->countByCriteria($criteria));
    }

    public function testItCanCountUsersByLineItemSlugCriteria(): void
    {
        $this->loadFixtureByFilename('100usersWithAssignments.yml');

        $criteria = (new FindUserCriteria())
            ->addLineItemSlugCriterion('lineItemSlug2', 'lineItemSlug3');

        self::assertSame(50, $this->subject->countByCriteria($criteria));
    }

    public function testItCanInsertMultipleUsers(): void
    {
        $userId1 = new UuidV6('00000001-0000-6000-0000-000000000000');
        $userId2 = new UuidV6('00000002-0000-6000-0000-000000000000');

        $user1 = new UserDto($userId1, 'test1', 'test');
        $user2 = new UserDto($userId2, 'test2', 'test');

        $userCollection = (new UserDtoCollection())
            ->add($user1)
            ->add($user2);

        $this->subject->insertMultipleNatively($userCollection);

        $users = $this->subject->findAll();
        self::assertCount(2, $users);

        self::assertTrue($userId1->equals($users[0]->getId()));
        self::assertTrue($userId2->equals($users[1]->getId()));
    }

    public function testItCanFindUsersByUsername(): void
    {
        $this->loadFixtureByFilename('100usersWithAssignments.yml');

        $expectedUsernames = ['user_1', 'user_2', 'user_3', 'user_4', 'user_5'];

        $users = $this->subject->findUsernames($expectedUsernames);

        self::assertCount(5, $users);

        foreach ($expectedUsernames as $expectedUsername) {
            self::assertContains($expectedUsername, $expectedUsernames);
        }
    }

    public function provideLimits(): array
    {
        return [
            'limit_1' => [1],
            'limit_3' => [3],
            'limit_10' => [10],
            'limit_99' => [99],
            'limit_100' => [100],
            'limit_101' => [101],
        ];
    }
}
