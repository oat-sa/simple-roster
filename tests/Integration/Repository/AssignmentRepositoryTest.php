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

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityNotFoundException;
use OAT\SimpleRoster\DataTransferObject\AssignmentDto;
use OAT\SimpleRoster\DataTransferObject\AssignmentDtoCollection;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\UuidV6;

class AssignmentRepositoryTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    /** @var AssignmentRepository */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();

        $this->subject = self::$container->get(AssignmentRepository::class);
    }

    public function testItCanFindAssignmentById(): void
    {
        $this->loadFixtureByFilename('usersWithStartedButStuckAssignments.yml');

        $assignment = $this->subject->findById(new UuidV6('00000001-0000-6000-0000-000000000000'));

        self::assertSame(Assignment::STATE_STARTED, $assignment->getState());
        self::assertSame(1, $assignment->getAttemptsCount());
    }

    public function testItThrowsExceptionIfAssignmentCannotBeFoundById(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage("Assignment with id = '00000999-0000-6000-0000-000000000000' cannot be found.");

        $this->subject->findById(new UuidV6('00000999-0000-6000-0000-000000000000'));
    }

    public function testItCanReturnAssignmentsByStateAndUpdatedAt(): void
    {
        $this->loadFixtureByFilename('usersWithStartedButStuckAssignments.yml');

        $dateTime = (new DateTime())->add(new DateInterval('P1D'));
        $assignments = $this->subject->findByStateAndUpdatedAtPaged(Assignment::STATE_STARTED, $dateTime);

        self::assertCount(9, $assignments->getIterator());
        self::assertCount(9, $assignments);
    }

    public function testItCanReturnAssignmentsByStateAndUpdatedAtPaginated(): void
    {
        $this->loadFixtureByFilename('usersWithStartedButStuckAssignments.yml');

        $dateTime = (new DateTime())->add(new DateInterval('P1D'));
        $assignments = $this->subject->findByStateAndUpdatedAtPaged(Assignment::STATE_STARTED, $dateTime, 2, 3);

        self::assertCount(3, $assignments->getIterator());
        self::assertCount(9, $assignments);
    }

    public function testItCanInsertMultipleAssignments(): void
    {
        $this->loadFixtureByFilename('usersWithStartedButStuckAssignments.yml');

        $lineItemId = new UuidV6('00000001-0000-6000-0000-000000000000');

        $assignmentId1 = new UuidV6('00000001-0000-6000-0000-000000000000');
        $assignment1 = new AssignmentDto($assignmentId1, Assignment::STATE_READY, $lineItemId, 'user1', 1);

        $assignmentId2 = new UuidV6('00000002-0000-6000-0000-000000000000');
        $assignment2 = new AssignmentDto($assignmentId2, Assignment::STATE_READY, $lineItemId, 'user2', 1);

        $assignmentId3 = new UuidV6('00000003-0000-6000-0000-000000000000');
        $assignment3 = new AssignmentDto($assignmentId3, Assignment::STATE_READY, $lineItemId, 'user3', 1);

        $assignmentCollection = (new AssignmentDtoCollection())
            ->add($assignment1)
            ->add($assignment2)
            ->add($assignment3);

        $this->subject->insertMultipleNatively($assignmentCollection);

        $assignments = $this->subject->findBy(['id' => [$assignmentId1, $assignmentId2, $assignmentId3]]);
        self::assertCount(3, $assignments);

        /** @var Assignment $assignment */
        foreach ($assignments as $assignment) {
            self::assertSame(Assignment::STATE_READY, $assignment->getState());
            self::assertSame(0, $assignment->getAttemptsCount());
        }
    }
}
