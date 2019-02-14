<?php declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Assignment;
use App\Exception\AssignmentNotFoundException;
use App\Repository\AssignmentRepository;
use App\Service\CompleteUserAssignmentService;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class CompleteUserAssignmentServiceTest extends TestCase
{
    /** @var CompleteUserAssignmentService */
    private $subject;

    /** @var AssignmentRepository|PHPUnit_Framework_MockObject_MockObject */
    private $assignmentRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->assignmentRepository = $this->createMock(AssignmentRepository::class);
        $this->subject = new CompleteUserAssignmentService($this->assignmentRepository);
    }

    public function testItThrowsExceptionIfAssignmentCannotBeFoundById(): void
    {
        $this->expectException(AssignmentNotFoundException::class);
        $this->expectExceptionMessage('Assignment with id `5` not found.');

        $this->subject->markAssignmentAsCompleted(5);
    }

    public function testItMarksAssignmentAsCompleted(): void
    {
        $assignment = (new Assignment())->setState(Assignment::STATE_STARTED);

        $this->assignmentRepository
            ->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn($assignment);

        $this->assignmentRepository
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Assignment $assignment) {
                return $assignment->getState() === Assignment::STATE_COMPLETED;
            }));

        $this->assignmentRepository
            ->expects($this->once())
            ->method('flush');

        $this->subject->markAssignmentAsCompleted(5);
    }
}
