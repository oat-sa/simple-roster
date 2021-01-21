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
use OAT\SimpleRoster\Exception\InvalidUsernameException;
use OAT\SimpleRoster\Generator\UserCacheIdGenerator;
use OAT\SimpleRoster\Repository\Criteria\FindUserCriteria;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

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
        $this->loadFixtureByFilename('100usersWithAssignments.yml');

        $this->subject = self::$container->get(UserRepository::class);
        $this->doctrineResultCacheImplementation = self::$container->get('doctrine.orm.default_result_cache');
        $this->userCacheIdGenerator = self::$container->get(UserCacheIdGenerator::class);
    }

    public function testItCanGetUserWithAssignmentsByUsername(): void
    {
        $user = $this->subject->findByUsernameWithAssignments('user_1');

        self::assertSame('user_1', $user->getUsername());
        self::assertCount(1, $user->getAssignments());
    }

    public function testItUsesResultCacheImplementationForGettingTheUserWithAssignments(): void
    {
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

        $this->subject->findAllUsernamesPaged(0, null);
    }

    /**
     * @dataProvider provideLimits
     */
    public function testItCanFindAllUsernamesPaged(int $limit): void
    {
        $lastUserId = null;
        $userIdIndex = 0;
        do {
            $resultSet = $this->subject->findAllUsernamesPaged($limit, $lastUserId);

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
        self::assertSame(100, $this->subject->countByCriteria());
    }

    public function testItCanCountUsersByUsernameCriteria(): void
    {
        $criteria = (new FindUserCriteria())
            ->addUsernameCriterion('user_1', 'user_10', 'user_73', 'user_88');

        self::assertSame(4, $this->subject->countByCriteria($criteria));
    }

    public function testItCanCountUsersByLineItemSlugCriteria(): void
    {
        $criteria = (new FindUserCriteria())
            ->addLineItemSlugCriterion('lineItemSlug2', 'lineItemSlug3');

        self::assertSame(50, $this->subject->countByCriteria($criteria));
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
