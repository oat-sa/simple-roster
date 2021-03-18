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

namespace OAT\SimpleRoster\Tests\Integration\Repository;

use OAT\SimpleRoster\DataTransferObject\AssignmentDto;
use OAT\SimpleRoster\DataTransferObject\AssignmentDtoCollection;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Repository\NativeAssignmentRepository;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\UuidV6;

class NativeAssignmentRepositoryTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    /** @var NativeAssignmentRepository */
    private $subject;

    /** @var AssignmentRepository */
    private $assignmentRepository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();
        $this->loadFixtureByFilename('usersWithStartedButStuckAssignments.yml');

        $this->subject = self::$container->get(NativeAssignmentRepository::class);
        $this->assignmentRepository = self::$container->get(AssignmentRepository::class);
    }

    public function testItCanInsertMultipleAssignments(): void
    {
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

        $this->subject->insertMultiple($assignmentCollection);

        $assignments = $this->assignmentRepository->findBy(['id' => [$assignmentId1, $assignmentId2, $assignmentId3]]);
        self::assertCount(3, $assignments);

        /** @var Assignment $assignment */
        foreach ($assignments as $assignment) {
            self::assertSame(Assignment::STATE_READY, $assignment->getState());
            self::assertSame(0, $assignment->getAttemptsCount());
        }
    }
}
