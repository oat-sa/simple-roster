<?php declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Traits\DatabaseManualFixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    use DatabaseManualFixturesTrait;

    /** @var UserRepository */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        static::bootKernel();
        $this->setUpDatabase();

        $this->loadFixtures([
            __DIR__ . '/../../../fixtures/100usersWithAssignments.yml',
        ]);

        $this->subject = self::$container->get(UserRepository::class);
    }

    public function testItCanFindAllUsers(): void
    {
        $users = $this->subject->findAllPaged();

        $this->assertCount(100, $users);
        $this->assertCount(100, $users->getIterator());
        $index = 1;
        /** @var User $user */
        foreach ($users->getIterator() as $user) {
            $this->assertEquals('user_' . $index, $user->getUsername());
            $index++;
        }
    }

    public function testItCanFindAllUsersPaged(): void
    {
        $users = $this->subject->findAllPaged(10, 5);

        $this->assertCount(100, $users);
        $this->assertCount(10, $users->getIterator());
        $index = 6;
        /** @var User $user */
        foreach ($users->getIterator() as $user) {
            $this->assertEquals('user_' . $index, $user->getUsername());
            $index++;
        }
    }
}
