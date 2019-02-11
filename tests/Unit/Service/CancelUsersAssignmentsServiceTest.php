<?php declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Assignment;
use App\Entity\User;
use App\Service\CancelUsersAssignmentsService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CancelUsersAssignmentsServiceTest extends TestCase
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var CancelUsersAssignmentsService */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->subject = new CancelUsersAssignmentsService($this->entityManager);
    }

    public function testItCancelsAllAssignmentsAndReturnsWithActualResultForAllUsers(): void
    {
        $readyAssignment = (new Assignment())->setState(Assignment::STATE_READY);
        $completedAssignment = (new Assignment())->setState(Assignment::STATE_COMPLETED);
        $startedAssignment = (new Assignment())->setState(Assignment::STATE_STARTED);

        $user1 = (new User())
            ->setUsername('user1')
            ->addAssignment($readyAssignment)
            ->addAssignment($completedAssignment)
            ->addAssignment($startedAssignment);

        $user2 = (new User())
            ->setUsername('user2')
            ->addAssignment($readyAssignment)
            ->addAssignment($completedAssignment)
            ->addAssignment($startedAssignment);

        $this->entityManager
            ->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->entityManager
            ->expects($this->once())
            ->method('commit');

        $this->assertEquals([
            'user1' => true,
            'user2' => true
        ], $this->subject->cancel($user1, $user2));

        foreach ($user1->getAssignments() as $assignment) {
            $this->assertEquals(Assignment::STATE_CANCELLED, $assignment->getState());
        }

        foreach ($user2->getAssignments() as $assignment) {
            $this->assertEquals(Assignment::STATE_CANCELLED, $assignment->getState());
        }
    }
}
