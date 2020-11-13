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

namespace OAT\SimpleRoster\Tests\Integration\Ingester\Ingester;

use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Ingester\Ingester\LineItemIngester;
use OAT\SimpleRoster\Ingester\Ingester\LtiInstanceIngester;
use OAT\SimpleRoster\Ingester\Ingester\UserIngester;
use OAT\SimpleRoster\Ingester\Source\IngesterSourceInterface;
use OAT\SimpleRoster\Ingester\Source\LocalCsvIngesterSource;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
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

        self::assertSame('user', $output->getIngesterType());
        self::assertTrue($output->isDryRun());
        self::assertSame(12, $output->getSuccessCount());
        self::assertFalse($output->hasFailures());

        self::assertEmpty($this->getRepository(User::class)->findAll());
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

        self::assertSame('user', $output->getIngesterType());
    }

    public function testIngestWithInvalidSource(): void
    {
        $this->prepareIngestionContext();

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Invalid/users.csv');

        $output = $this->subject->ingest($source, false);

        self::assertSame(1, $output->getSuccessCount());
        self::assertTrue($output->hasFailures());
        self::assertCount(1, $output->getFailures());

        self::assertCount(1, $this->getRepository(User::class)->findAll());

        $user1 = $this->getRepository(User::class)->find(1);
        self::assertSame('user_1', $user1->getUsername());

        $failure = current($output->getFailures());
        self::assertSame(2, $failure->getLineNumber());
        self::assertSame(
            [
                'username' => 'user_1',
                'password' => 'password1',
                'slug' => 'gra13_ita_1',
            ],
            $failure->getData()
        );
        self::assertStringContainsString('UNIQUE constraint failed: users.username', $failure->getReason());
    }

    public function testIngestWithValidSource(): void
    {
        $this->prepareIngestionContext();

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/users.csv');

        $output = $this->subject->ingest($source, false);

        self::assertSame(12, $output->getSuccessCount());
        self::assertFalse($output->hasFailures());

        self::assertCount(12, $this->getRepository(User::class)->findAll());

        $user1 = $this->getRepository(User::class)->find(1);
        self::assertSame('user_1', $user1->getUsername());

        $user12 = $this->getRepository(User::class)->find(12);
        self::assertSame('user_12', $user12->getUsername());
    }

    public function testItCanIngestUsersWithGroupId(): void
    {
        $this->prepareIngestionContext();

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/users-with-groupId.csv');

        $output = $this->subject->ingest($source, false);

        self::assertSame(12, $output->getSuccessCount());
        self::assertFalse($output->hasFailures());

        self::assertCount(12, $this->getRepository(User::class)->findAll());

        $usersInGroup1 = $this->getRepository(User::class)->findBy(['groupId' => 'group_1']);
        self::assertCount(3, $usersInGroup1);

        $usersInGroup2 = $this->getRepository(User::class)->findBy(['groupId' => 'group_2']);
        self::assertCount(4, $usersInGroup2);

        $usersInGroup3 = $this->getRepository(User::class)->findBy(['groupId' => 'group_3']);
        self::assertCount(5, $usersInGroup3);
    }

    private function createIngesterSource(string $path): IngesterSourceInterface
    {
        return (new LocalCsvIngesterSource())->setPath($path);
    }

    private function prepareIngestionContext(): void
    {
        static::$container->get(LtiInstanceIngester::class)->ingest(
            $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/lti-instances.csv'),
            false
        );

        static::$container->get(LineItemIngester::class)->ingest(
            $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/line-items.csv'),
            false
        );
    }
}
