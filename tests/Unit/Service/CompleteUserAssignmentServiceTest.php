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

namespace App\Tests\Unit\Service;

use App\Entity\Assignment;
use App\Entity\Infrastructure;
use App\Entity\LineItem;
use App\Entity\User;
use App\Exception\AssignmentNotFoundException;
use App\Repository\AssignmentRepository;
use App\Service\CompleteUserAssignmentService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CompleteUserAssignmentServiceTest extends TestCase
{
    /** @var CompleteUserAssignmentService */
    private $subject;

    /** @var AssignmentRepository|MockObject */
    private $assignmentRepository;

    /** @var LoggerInterface|MockObject */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assignmentRepository = $this->createMock(AssignmentRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new CompleteUserAssignmentService($this->assignmentRepository, $this->logger);
    }

    public function testItThrowsExceptionIfAssignmentCannotBeFoundById(): void
    {
        $this->expectException(AssignmentNotFoundException::class);
        $this->expectExceptionMessage("Assignment with id '5' not found.");

        $this->subject->markAssignmentAsCompleted(5);
    }

    public function testItMarksAssignmentAsCompleted(): void
    {
        $user = (new User())->setUsername('expectedUsername');

        $lineItem = (new LineItem())
            ->setInfrastructure(new Infrastructure())
            ->setUri('uri')
            ->setLabel('label')
            ->setSlug('slug')
            ->setMaxAttempts(1);

        $assignment = (new Assignment())
            ->setState(Assignment::STATE_STARTED)
            ->setLineItem($lineItem)
            ->setUser($user)
            ->setAttemptsCount(1);

        $this->assignmentRepository
            ->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn($assignment);

        $this->assignmentRepository
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(static function (Assignment $assignment) {
                return $assignment->getState() === Assignment::STATE_COMPLETED;
            }));

        $this->assignmentRepository
            ->expects($this->once())
            ->method('flush');

        $this->subject->markAssignmentAsCompleted(5);
    }

    public function testItMarksAssignmentAsReady(): void
    {
        $user = (new User())->setUsername('expectedUsername');

        $lineItem = (new LineItem())
            ->setInfrastructure(new Infrastructure())
            ->setUri('uri')
            ->setLabel('label')
            ->setSlug('slug')
            ->setMaxAttempts(2);

        $assignment = (new Assignment())
            ->setState(Assignment::STATE_STARTED)
            ->setLineItem($lineItem)
            ->setUser($user)
            ->setAttemptsCount(1);

        $this->assignmentRepository
            ->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn($assignment);

        $this->assignmentRepository
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(static function (Assignment $assignment) {
                return $assignment->getState() === Assignment::STATE_READY;
            }));

        $this->assignmentRepository
            ->expects($this->once())
            ->method('flush');

        $this->subject->markAssignmentAsCompleted(5);
    }

    public function testItLogsSuccessfulCompletion(): void
    {
        $user = (new User())->setUsername('expectedUsername');

        $lineItem = (new LineItem())
            ->setInfrastructure(new Infrastructure())
            ->setUri('uri')
            ->setLabel('label')
            ->setSlug('slug');

        $assignment = (new Assignment())
            ->setState(Assignment::STATE_STARTED)
            ->setLineItem($lineItem)
            ->setUser($user);

        $this->assignmentRepository
            ->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn($assignment);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                "Assignment with id='5' of user with username='expectedUsername' has been marked as completed.",
                ['lineItem' => $assignment->getLineItem()]
            );

        $this->subject->markAssignmentAsCompleted(5);
    }
}
