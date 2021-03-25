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

namespace OAT\SimpleRoster\Tests\Integration\Entity;

use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Exception\AssignmentNotFoundException;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\UuidV6;

class UserTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();
    }

    public function testItCanRetrieveAndRemoveAssignments(): void
    {
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        /** @var User $subject */
        $subject = $this->getRepository(User::class)->find(new UuidV6('00000001-0000-6000-0000-000000000000'));

        /** @var Assignment $assignment */
        $assignment = $this->getRepository(Assignment::class)->find(new UuidV6('00000001-0000-6000-0000-000000000000'));

        self::assertCount(1, $subject->getAssignments());
        self::assertCount(1, $subject->getAvailableAssignments());

        self::assertSame($assignment, current($subject->getAvailableAssignments()));
    }

    public function testItThrowsExceptionIfUserHasNoAssignments(): void
    {
        $this->expectException(AssignmentNotFoundException::class);
        $this->expectExceptionMessage("User 'testUser' does not have any assignments.");

        (new User(new UuidV6(), 'testUser', 'testPassword'))->getLastAssignment();
    }
}
