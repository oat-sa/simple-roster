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
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Exception\AssignmentNotFoundException;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Service\CompleteUserAssignmentService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\UuidV6;

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
        $this->expectExceptionMessage("Assignment with id '00000001-0000-6000-0000-000000000000' not found.");

        $this->assignmentRepository
            ->method('findById')
            ->willThrowException(new EntityNotFoundException());

        $this->subject->markAssignmentAsCompleted(new UuidV6('00000001-0000-6000-0000-000000000000'));
    }

    public function testItMarksAssignmentAsCompleted(): void
    {
        $user = (new User())->setUsername('expectedUsername');

        $lineItem = new LineItem(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'testLabel',
            'testUri',
            'testSlug',
            LineItem::STATUS_ENABLED,
            1
        );

        $assignmentId = new UuidV6('00000001-0000-6000-0000-000000000000');
        $assignment = new Assignment($assignmentId, Assignment::STATUS_STARTED, $lineItem, 1);

        $user->addAssignment($assignment);

        $this->assignmentRepository
            ->expects(self::once())
            ->method('findById')
            ->with($assignmentId)
            ->willReturn($assignment);

        $this->assignmentRepository
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (Assignment $assignment) {
                return $assignment->getStatus() === Assignment::STATUS_COMPLETED;
            }));

        $this->assignmentRepository
            ->expects(self::once())
            ->method('flush');

        $this->subject->markAssignmentAsCompleted($assignmentId);
    }

    public function testItMarksAssignmentAsReady(): void
    {
        $user = (new User())->setUsername('expectedUsername');

        $lineItem = new LineItem(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'testLabel',
            'testUri',
            'testSlug',
            LineItem::STATUS_ENABLED,
            2
        );

        $assignmentId = new UuidV6('00000001-0000-6000-0000-000000000000');
        $assignment = new Assignment($assignmentId, Assignment::STATUS_STARTED, $lineItem, 1);

        $user->addAssignment($assignment);

        $this->assignmentRepository
            ->expects(self::once())
            ->method('findById')
            ->with($assignmentId)
            ->willReturn($assignment);

        $this->assignmentRepository
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (Assignment $assignment) {
                return $assignment->getStatus() === Assignment::STATUS_READY;
            }));

        $this->assignmentRepository
            ->expects(self::once())
            ->method('flush');

        $this->subject->markAssignmentAsCompleted($assignmentId);
    }

    public function testItLogsSuccessfulCompletion(): void
    {
        $user = (new User())->setUsername('expectedUsername');

        $lineItem = new LineItem(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'testLabel',
            'testUri',
            'testSlug',
            LineItem::STATUS_ENABLED
        );

        $assignmentId = new UuidV6('00000001-0000-6000-0000-000000000000');
        $assignment = new Assignment($assignmentId, Assignment::STATUS_STARTED, $lineItem);

        $user->addAssignment($assignment);

        $this->assignmentRepository
            ->expects(self::once())
            ->method('findById')
            ->with($assignmentId)
            ->willReturn($assignment);

        $this->logger
            ->expects(self::once())
            ->method('info')
            ->with(
                "Assignment with id='00000001-0000-6000-0000-000000000000' of user with username='expectedUsername' " .
                "has been marked as completed.",
                ['lineItem' => $assignment->getLineItem()]
            );

        $this->subject->markAssignmentAsCompleted($assignmentId);
    }
}
