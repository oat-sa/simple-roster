<?php declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Assignment;
use App\Entity\Infrastructure;
use App\Entity\LineItem;
use App\Entity\User;
use App\Exception\AssignmentNotFoundException;
use App\Repository\AssignmentRepository;
use App\Service\CompleteUserAssignmentService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use PHPUnit_Framework_MockObject_MockObject;

class CompleteUserAssignmentServiceTest extends TestCase
{
    /** @var CompleteUserAssignmentService */
    private $subject;

    /** @var AssignmentRepository|PHPUnit_Framework_MockObject_MockObject */
    private $assignmentRepository;

    /** @var LoggerInterface|PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    protected function setUp()
    {
        parent::setUp();

        $this->assignmentRepository = $this->createMock(AssignmentRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new CompleteUserAssignmentService($this->assignmentRepository, $this->logger);
    }

    public function testItThrowsExceptionIfAssignmentCannotBeFoundById(): void
    {
        $this->expectException(AssignmentNotFoundException::class);
        $this->expectExceptionMessage('Assignment with id `5` not found.');

        $this->subject->markAssignmentAsCompleted(5);
    }

    public function testItMarksAssignmentAsCompleted(): void
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
                'Assignment with id=`5` of user with username=`expectedUsername` has been marked as completed.',
                ['lineItem' => $assignment->getLineItem()]
            );

        $this->subject->markAssignmentAsCompleted(5);
    }
}
