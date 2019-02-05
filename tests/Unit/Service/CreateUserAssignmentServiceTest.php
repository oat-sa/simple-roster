<?php declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Assignment;
use App\Entity\LineItem;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\CreateUserAssignmentService;
use PHPUnit\Framework\TestCase;

class CreateUserAssignmentServiceTest extends TestCase
{
    /** @var CreateUserAssignmentService */
    private $subject;

    /** @var UserRepository */
    private $userRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepository::class);
        $this->subject = new CreateUserAssignmentService($this->userRepository);
    }

    /**
     * @expectedException \Doctrine\ORM\EntityNotFoundException
     * @expectedExceptionMessage Assignment cannot be created for user 'testUser'. No previous assignments were found in database.
     */
    public function testItThrowsExceptionIfNoPreviousAssignmentWasFound(): void
    {
        $this->subject->create((new User())->setUsername('testUser'));
    }

    public function testItCreatesNewAssignmentWithLastLineItemAndWithReadyState(): void
    {
        $expectedLineItem = new LineItem();
        $lastAssignment = (new Assignment())->setLineItem($expectedLineItem);
        $user = (new User())->addAssignment($lastAssignment);

        $this->userRepository
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        $createdAssignment = $this->subject->create($user);

        $this->assertEquals(Assignment::STATE_READY, $createdAssignment->getState());
        $this->assertEquals($expectedLineItem, $createdAssignment->getLineItem());
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
