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

        $this->setUpDatabase();

        $this->loadFixtures([
            __DIR__ . '/../../../fixtures/100usersWithAssignments.yml',
        ]);

        $this->subject = self::$container->get(UserRepository::class);
    }

    public function testItCanFindAllUsers(): void
    {
        $users = $this->subject->findAllPaginated();

        $this->assertCount(100, $users);
        $this->assertCount(100, $users->getIterator());

        $index = 1;
        foreach ($users as $user) {
            $this->assertEquals('user_' . $index, $user->getUsername());
            $index++;
        }
    }

    public function testItCanFindAllUsersPaginated(): void
    {
        $users = $this->subject->findAllPaginated(10, 5);

        $this->assertCount(100, $users);
        $this->assertCount(10, $users->getIterator());

        $index = 6;
        foreach ($users as $user) {
            $this->assertEquals('user_' . $index, $user->getUsername());
            $index++;
        }
    }
}
