<?php declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Assignment;
use App\Entity\User;
use App\Service\CreateUserAssignmentService;
use App\Service\CreateUsersAssignmentsService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PHPUnit\Framework\TestCase;

class CreateUsersAssignmentsServiceTest extends TestCase
{
    /** @var CreateUsersAssignmentsService */
    private $subject;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var CreateUserAssignmentService */
    private $createUserAssignmentService;

    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->createUserAssignmentService = $this->createMock(CreateUserAssignmentService::class);
        $this->subject = new CreateUsersAssignmentsService($this->createUserAssignmentService, $this->entityManager);
    }

    public function testItReturnsWithActualResultForAllUsers(): void
    {
        $user1 = (new User())->setUsername('user1');
        $user2 = (new User())->setUsername('user2');
        $user3 = (new User())->setUsername('user3');
        $user4 = (new User())->setUsername('user4');

        $this->createUserAssignmentService
            ->method('create')
            ->willReturnCallback(function (User $user) {
                if (in_array($user->getUsername(), ['user1', 'user2'])) {
                    throw new Exception('Not ok');
                }

                return new Assignment();
            });

        $this->assertEquals([
            'user1' => 'failure',
            'user2' => 'failure',
            'user3' => 'success',
            'user4' => 'success',
        ], $this->subject->create($user1, $user2, $user3, $user4));
    }

    public function testItCommitsTransactionIfThereIsNoError(): void
    {
        $user1 = (new User())->setUsername('user1');
        $user2 = (new User())->setUsername('user2');
        $user3 = (new User())->setUsername('user3');
        $user4 = (new User())->setUsername('user4');

        $this->createUserAssignmentService
            ->method('create')
            ->willReturn(new Assignment());

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->entityManager
            ->expects($this->once())
            ->method('commit');

        $this->entityManager
            ->expects($this->never())
            ->method('rollback');

        $this->subject->create($user1, $user2, $user3, $user4);
    }

    public function testItRollsBackWholeTransactionIfAnyErrorOccurs(): void
    {
        $user1 = (new User())->setUsername('user1');
        $user2 = (new User())->setUsername('user2');
        $user3 = (new User())->setUsername('user3');
        $user4 = (new User())->setUsername('user4');

        $this->createUserAssignmentService
            ->method('create')
            ->willReturnCallback(function (User $user) {
                if ($user->getUsername() === 'user3') {
                    throw new Exception('Not ok');
                }

                return new Assignment();
            });

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $this->entityManager
            ->expects($this->never())
            ->method('commit');

        $this->entityManager
            ->expects($this->once())
            ->method('rollback');

        $this->subject->create($user1, $user2, $user3, $user4);
    }
}
