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

namespace OAT\SimpleRoster\Tests\Unit\Service;

use Doctrine\ORM\EntityNotFoundException;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\Infrastructure;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Exception\AssignmentNotFoundException;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Service\CompleteUserAssignmentService;
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

        $this->assignmentRepository
            ->method('findById')
            ->willThrowException(new EntityNotFoundException());

        $this->subject->markAssignmentAsCompleted(5);
    }

    public function testItMarksAssignmentAsCompleted(): void
    {
        $user = (new User())->setUsername('expectedUsername');

        $lineItem = (new LineItem())
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
            ->expects(self::once())
            ->method('findById')
            ->with(5)
            ->willReturn($assignment);

        $this->assignmentRepository
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (Assignment $assignment) {
                return $assignment->getState() === Assignment::STATE_COMPLETED;
            }));

        $this->assignmentRepository
            ->expects(self::once())
            ->method('flush');

        $this->subject->markAssignmentAsCompleted(5);
    }

    public function testItMarksAssignmentAsReady(): void
    {
        $user = (new User())->setUsername('expectedUsername');

        $lineItem = (new LineItem())
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
            ->expects(self::once())
            ->method('findById')
            ->with(5)
            ->willReturn($assignment);

        $this->assignmentRepository
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (Assignment $assignment) {
                return $assignment->getState() === Assignment::STATE_READY;
            }));

        $this->assignmentRepository
            ->expects(self::once())
            ->method('flush');

        $this->subject->markAssignmentAsCompleted(5);
    }

    public function testItLogsSuccessfulCompletion(): void
    {
        $user = (new User())->setUsername('expectedUsername');

        $lineItem = (new LineItem())
            ->setUri('uri')
            ->setLabel('label')
            ->setSlug('slug');

        $assignment = (new Assignment())
            ->setState(Assignment::STATE_STARTED)
            ->setLineItem($lineItem)
            ->setUser($user);

        $this->assignmentRepository
            ->expects(self::once())
            ->method('findById')
            ->with(5)
            ->willReturn($assignment);

        $this->logger
            ->expects(self::once())
            ->method('info')
            ->with(
                "Assignment with id='5' of user with username='expectedUsername' has been marked as completed.",
                ['lineItem' => $assignment->getLineItem()]
            );

        $this->subject->markAssignmentAsCompleted(5);
    }
}
