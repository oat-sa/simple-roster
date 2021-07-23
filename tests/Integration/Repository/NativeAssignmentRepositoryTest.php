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

        $this->subject = self::getContainer()->get(NativeAssignmentRepository::class);
        $this->assignmentRepository = self::getContainer()->get(AssignmentRepository::class);
    }

    public function testItCanInsertMultipleAssignments(): void
    {
        $assignment1 = new AssignmentDto(Assignment::STATE_READY, 1, 'user1', 1);
        $assignment2 = new AssignmentDto(Assignment::STATE_READY, 1, 'user2', 1);
        $assignment3 = new AssignmentDto(Assignment::STATE_READY, 1, 'user3', 1);

        $assignmentCollection = (new AssignmentDtoCollection())
            ->add($assignment1)
            ->add($assignment2)
            ->add($assignment3);

        $this->subject->insertMultiple($assignmentCollection);

        $assignments = $this->assignmentRepository->findBy(['id' => [11, 12, 13]]);
        self::assertCount(3, $assignments);

        /** @var Assignment $assignment */
        foreach ($assignments as $assignment) {
            self::assertSame(Assignment::STATE_READY, $assignment->getState());
            self::assertSame(0, $assignment->getAttemptsCount());
        }
    }
}
