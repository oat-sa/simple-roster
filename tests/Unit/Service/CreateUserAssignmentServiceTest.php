<?php declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Assignment;
use App\Entity\LineItem;
use App\Entity\User;
use App\Repository\AssignmentRepository;
use App\Service\CreateUserAssignmentService;
use Doctrine\ORM\EntityNotFoundException;
use PHPUnit\Framework\TestCase;

class CreateUserAssignmentServiceTest extends TestCase
{
    /** @var CreateUserAssignmentService */
    private $subject;

    /** @var AssignmentRepository */
    private $assignmentRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->assignmentRepository = $this->createMock(AssignmentRepository::class);
        $this->subject = new CreateUserAssignmentService($this->assignmentRepository);
    }

    public function testItThrowsExceptionIfNoPreviousAssignmentWasFound(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage("Assignment cannot be created for user 'testUser'. No previous assignments were found in database.");

        $this->subject->create((new User())->setUsername('testUser'));
    }

    public function testItCreatesNewAssignmentWithLastLineItemAndWithReadyState(): void
    {
        $expectedLineItem = new LineItem();
        $lastAssignment = (new Assignment())->setLineItem($expectedLineItem);
        $user = (new User())->addAssignment($lastAssignment);

        $this->assignmentRepository
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function (Assignment $assignment) use ($expectedLineItem) {
                return $assignment->getState() === Assignment::STATE_READY
                    && $assignment->getLineItem() === $expectedLineItem;
            });

        $this->subject->create($user);
    }

    public function testItCancelsAllPreviousAssignmentsBeforeCreatingNewOne(): void
    {
        $previousAssignment1 = (new Assignment())
            ->setLineItem(new LineItem())
            ->setState(Assignment::STATE_STARTED);

        $previousAssignment2 = (new Assignment())->setState(Assignment::STATE_READY);
        $previousAssignment3 = (new Assignment())->setState(Assignment::STATE_COMPLETED);

        $user = (new User)
            ->addAssignment($previousAssignment3)
            ->addAssignment($previousAssignment2)
            ->addAssignment($previousAssignment1);

        $this->subject->create($user);

        $this->assertEquals(Assignment::STATE_CANCELLED, $previousAssignment1->getState());
        $this->assertEquals(Assignment::STATE_CANCELLED, $previousAssignment2->getState());
        $this->assertEquals(Assignment::STATE_CANCELLED, $previousAssignment3->getState());
    }
}
