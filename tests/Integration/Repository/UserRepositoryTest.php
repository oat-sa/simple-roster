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

namespace App\Tests\Integration\Repository;

use App\Exception\InvalidUsernameException;
use App\Generator\UserCacheIdGenerator;
use App\Repository\UserRepository;
use App\Tests\Traits\DatabaseTestingTrait;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityNotFoundException;
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

        $this->setUpDatabase();
        $this->loadFixtureByFilename('100usersWithAssignments.yml');

        $this->subject = self::$container->get(UserRepository::class);
        $this->doctrineResultCacheImplementation = self::$container->get('doctrine.orm.default_result_cache');
        $this->userCacheIdGenerator = self::$container->get(UserCacheIdGenerator::class);
    }

    public function testItCanGetUserWithAssignmentsByUsername(): void
    {
        $user = $this->subject->getByUsernameWithAssignments('user_1');

        $this->assertSame('user_1', $user->getUsername());
        $this->assertCount(1, $user->getAssignments());
    }

    public function testItUsesResultCacheImplementationForGettingTheUserWithAssignments(): void
    {
        $username = 'user_1';
        $expectedResultCacheId = $this->userCacheIdGenerator->generate($username);

        $this->assertFalse($this->doctrineResultCacheImplementation->contains($expectedResultCacheId));

        $this->subject->getByUsernameWithAssignments($username);

        $this->assertTrue($this->doctrineResultCacheImplementation->contains($expectedResultCacheId));
    }

    public function testItThrowsExceptionIfWeTryToGetUserWithAssignmentsWithEmptyUsername(): void
    {
        $this->expectException(InvalidUsernameException::class);
        $this->expectExceptionMessage('Empty username received.');

        $this->subject->getByUsernameWithAssignments('');
    }

    public function testItThrowsExceptionIfUserCannotBeFound(): void
    {
        $this->expectException(EntityNotFoundException::class);

        $this->subject->getByUsernameWithAssignments('nonExistingUser');
    }
}
