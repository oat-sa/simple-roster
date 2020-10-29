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

namespace App\Tests\Integration\Repository;

use App\DataTransferObject\AssignmentDto;
use App\DataTransferObject\AssignmentDtoCollection;
use App\Entity\Assignment;
use App\Repository\AssignmentRepository;
use App\Repository\NativeAssignmentRepository;
use App\Tests\Traits\DatabaseTestingTrait;
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

        $this->subject = self::$container->get(NativeAssignmentRepository::class);
        $this->assignmentRepository = self::$container->get(AssignmentRepository::class);
    }

    public function testItCanInsertMultipleAssignments(): void
    {
        $assignment1 = new AssignmentDto(Assignment::STATE_READY, 1, 1);
        $assignment2 = new AssignmentDto(Assignment::STATE_READY, 1, 1);
        $assignment3 = new AssignmentDto(Assignment::STATE_READY, 1, 1);

        $assignmentCollection = (new AssignmentDtoCollection())
            ->add($assignment1)
            ->add($assignment2)
            ->add($assignment3);

        $this->subject->insertMultiple($assignmentCollection);

        self::assertCount(3, $this->assignmentRepository->findBy(['id' => [11, 12, 13]]));
    }
}
