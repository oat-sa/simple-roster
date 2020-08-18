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

namespace App\Tests\Integration\Ingester\Ingester;

use App\Entity\User;
use App\Ingester\Ingester\InfrastructureIngester;
use App\Ingester\Ingester\LineItemIngester;
use App\Ingester\Ingester\UserIngester;
use App\Ingester\Source\IngesterSourceInterface;
use App\Ingester\Source\LocalCsvIngesterSource;
use App\Repository\LineItemRepository;
use App\Tests\Traits\DatabaseTestingTrait;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserIngesterTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    /** @var UserIngester */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();

        $this->subject = new UserIngester(
            self::$container->get(LineItemRepository::class),
            $this->getManagerRegistry()
        );
    }

    public function testDryRunIngest(): void
    {
        $this->prepareIngestionContext();

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/users.csv');

        $output = $this->subject->ingest($source);

        $this->assertSame('user', $output->getIngesterType());
        $this->assertTrue($output->isDryRun());
        $this->assertSame(12, $output->getSuccessCount());
        $this->assertFalse($output->hasFailures());

        $this->assertEmpty($this->getRepository(User::class)->findAll());
    }

    public function testIngestWithEmptyLineItems(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Cannot ingest 'user' since line-item table is empty.");

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/users.csv');

        $this->subject->ingest($source, false);
    }

    public function testItHasUserIngesterType(): void
    {
        $this->prepareIngestionContext();

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Invalid/users.csv');

        $output = $this->subject->ingest($source, false);

        $this->assertSame('user', $output->getIngesterType());
    }

    public function testIngestWithInvalidSource(): void
    {
        $this->prepareIngestionContext();

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Invalid/users.csv');

        $output = $this->subject->ingest($source, false);

        $this->assertSame(1, $output->getSuccessCount());
        $this->assertTrue($output->hasFailures());
        $this->assertCount(1, $output->getFailures());

        $this->assertCount(1, $this->getRepository(User::class)->findAll());

        $user1 = $this->getRepository(User::class)->find(1);
        $this->assertSame('user_1', $user1->getUsername());

        $failure = current($output->getFailures());
        $this->assertSame(2, $failure->getLineNumber());
        $this->assertSame(
            [
                'username' => 'user_1',
                'password' => 'password1',
                'slug' => 'gra13_ita_1',
            ],
            $failure->getData()
        );
        $this->assertStringContainsString('UNIQUE constraint failed: users.username', $failure->getReason());
    }

    public function testIngestWithValidSource(): void
    {
        $this->prepareIngestionContext();

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/users.csv');

        $output = $this->subject->ingest($source, false);

        $this->assertSame(12, $output->getSuccessCount());
        $this->assertFalse($output->hasFailures());

        $this->assertCount(12, $this->getRepository(User::class)->findAll());

        $user1 = $this->getRepository(User::class)->find(1);
        $this->assertSame('user_1', $user1->getUsername());

        $user12 = $this->getRepository(User::class)->find(12);
        $this->assertSame('user_12', $user12->getUsername());
    }

    public function testItCanIngestUsersWithGroupId(): void
    {
        $this->prepareIngestionContext();

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/users-with-groupId.csv');

        $output = $this->subject->ingest($source, false);

        $this->assertSame(12, $output->getSuccessCount());
        $this->assertFalse($output->hasFailures());

        $this->assertCount(12, $this->getRepository(User::class)->findAll());

        $usersInGroup1 = $this->getRepository(User::class)->findBy(['groupId' => 'group_1']);
        $this->assertCount(3, $usersInGroup1);

        $usersInGroup2 = $this->getRepository(User::class)->findBy(['groupId' => 'group_2']);
        $this->assertCount(4, $usersInGroup2);

        $usersInGroup3 = $this->getRepository(User::class)->findBy(['groupId' => 'group_3']);
        $this->assertCount(5, $usersInGroup3);
    }

    private function createIngesterSource(string $path): IngesterSourceInterface
    {
        return (new LocalCsvIngesterSource())->setPath($path);
    }

    private function prepareIngestionContext(): void
    {
        static::$container->get(InfrastructureIngester::class)->ingest(
            $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv'),
            false
        );

        static::$container->get(LineItemIngester::class)->ingest(
            $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/line-items.csv'),
            false
        );
    }
}
