<?php declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Infrastructure;
use App\Entity\User;
use App\Repository\AssignmentRepository;
use App\Repository\UserRepository;
use App\Tests\Traits\DatabaseManualFixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AssignmentRepositoryTest extends WebTestCase
{
    use DatabaseManualFixturesTrait;

    /** @var AssignmentRepository */
    private $subject;

    /** @var UserRepository */
    private $userRepository;

    protected function setUp()
    {
        parent::setUp();

        self::bootKernel();
        $this->setUpDatabase();

        $this->userRepository = self::$container->get(UserRepository::class);
        $this->subject = self::$container->get(AssignmentRepository::class);
    }

    public function test(): void
    {
        $this->assertTrue(true);
    }

//    public function testTest(): void
//    {
//        $this->loadFixtures([
//            __DIR__ . '/../../../fixtures/infrastructures.yml'
//        ]);
//
//        $infra = $this->getRepository(Infrastructure::class)->findAll();
//        $infra = $this->getRepository(User::class)->findAll();
//
//        var_dump($infra); exit;
//    }

//    public function testItCanReturnAssignmentsByStateAndUpdatedAt(): void
//    {
//        /** @var User $user */
//        $user = $this->userRepository->findBy(['username' => 'userWithMultipleStartedAssignments'])[0];
//
//        $this->assertEquals(
//            $user->getAssignments()->toArray(),
//            $this->subject
//                ->findAllByStateAndUpdatedAtPaged(Assignment::STATE_STARTED, new DateTime('+1 minute'), 0, 4)
//                ->getIterator()->getArrayCopy()
//        );
//    }
}
