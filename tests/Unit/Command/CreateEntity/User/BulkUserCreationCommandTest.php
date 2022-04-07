<?php

/*
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
 *  Copyright (c) 2022 (original work) Open Assessment Technologies S.A.
 */

namespace OAT\SimpleRoster\Tests\Unit\Command\CreateEntity\User;

use League\Flysystem\FileExistsException;
use OAT\SimpleRoster\Command\CreateEntity\User\BulkUserCreationCommand;
use OAT\SimpleRoster\DataTransferObject\UserCreationResult;
use OAT\SimpleRoster\Service\AwsS3\FolderSyncService;
use OAT\SimpleRoster\Service\Bulk\BulkCreateUsersServiceConsoleProxy;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use InvalidArgumentException;

class BulkUserCreationCommandTest extends KernelTestCase
{
    public function testBasicWithSlug(): void
    {
        $this->getCommandForBasicPipeline()->execute(
            [
                '-s' => '21LINUM_NUM',
                '-b' => '10',
                'user-prefix' => 'QA,OAT,TE',
                '-g' => 'TestCollege',
            ],
            ['capture_stderr_separately' => true]
        );
    }

    public function testBasicWithId(): void
    {
        $this->getCommandForBasicPipeline()->execute(
            [
                '-i' => '5',
                '-b' => '10',
                'user-prefix' => 'QA,OAT,TE',
                '-g' => 'TestCollege',
            ],
            ['capture_stderr_separately' => true]
        );
    }

    public function testInvalidSlugIdCombination(): void
    {
        self::expectException(InvalidArgumentException::class);
        $this->getCommandForOptionTest()->execute(
            [
                '-s' => '21LINUM_NUM',
                '-i' => '10',
                '-b' => '10',
                'user-prefix' => 'QA,OAT,TE',
                '-g' => 'TestCollege',
            ],
            ['capture_stderr_separately' => true]
        );
    }

    public function testInvalidBatch(): void
    {
        self::expectException(InvalidArgumentException::class);
        $this->getCommandForOptionTest()->execute(
            [
                '-s' => '21LINUM_NUM',
                '-b' => 'zagzag',
                'user-prefix' => 'QA,OAT,TE',
                '-g' => 'TestCollege',
            ],
            ['capture_stderr_separately' => true]
        );
    }

    public function testInvalidBatchUserPrefixes(): void
    {
        self::expectException(InvalidArgumentException::class);
        $this->getCommandForOptionTest()->execute(
            [
                '-s' => '21LINUM_NUM',
                '-b' => '10',
                'user-prefix' => ',,,,',
                '-g' => 'TestCollege',
            ],
            ['capture_stderr_separately' => true]
        );
    }

    public function testInvalidBatchItemSlugs(): void
    {
        self::expectException(InvalidArgumentException::class);
        $this->getCommandForOptionTest()->execute(
            [
                '-s' => ',,,,',
                '-b' => '10',
                'user-prefix' => 'QA,OAT,TE',
                '-g' => 'TestCollege',
            ],
            ['capture_stderr_separately' => true]
        );
    }

    public function testInvalidBatchItemIds(): void
    {
        self::expectException(InvalidArgumentException::class);
        $this->getCommandForOptionTest()->execute(
            [
                '-i' => ',,,,',
                '-b' => '10',
                'user-prefix' => 'QA,OAT,TE',
                '-g' => 'TestCollege',
            ],
            ['capture_stderr_separately' => true]
        );
    }

    public function testFilesystemException(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $bulkCreateServiceProxyMock = self::createMock(BulkCreateUsersServiceConsoleProxy::class);
        $bulkCreateServiceProxyMock
            ->expects(self::once())
            ->method('createUsers')
            ->willReturn(new UserCreationResult('test', ['test']));

        $folderSyncServiceMock = self::createMock(FolderSyncService::class);
        $folderSyncServiceMock
            ->expects(self::once())
            ->method('sync')
            ->willThrowException(new FileExistsException('test/path'));

        $kernel->getContainer()->set(BulkCreateUsersServiceConsoleProxy::class, $bulkCreateServiceProxyMock);
        $kernel->getContainer()->set(FolderSyncService::class, $folderSyncServiceMock);

        $commandTester = new CommandTester($application->find(BulkUserCreationCommand::NAME));
        $commandTester->execute(
            [
                '-s' => '21LINUM_NUM',
                '-b' => '10',
                'user-prefix' => 'QA,OAT,TE',
                '-g' => 'TestCollege',
            ],
            ['capture_stderr_separately' => true]
        );
    }

    public function testGenerationServiceException(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $bulkCreateServiceProxyMock = self::createMock(BulkCreateUsersServiceConsoleProxy::class);
        $bulkCreateServiceProxyMock
            ->expects(self::once())
            ->method('createUsers')
            ->willThrowException(new RuntimeException());

        $folderSyncServiceMock = self::createMock(FolderSyncService::class);
        $folderSyncServiceMock
            ->expects(self::never())
            ->method('sync');

        $kernel->getContainer()->set(BulkCreateUsersServiceConsoleProxy::class, $bulkCreateServiceProxyMock);
        $kernel->getContainer()->set(FolderSyncService::class, $folderSyncServiceMock);

        $commandTester = new CommandTester($application->find(BulkUserCreationCommand::NAME));
        $commandTester->execute(
            [
                '-s' => '21LINUM_NUM',
                '-b' => '10',
                'user-prefix' => 'QA,OAT,TE',
                '-g' => 'TestCollege',
            ],
            ['capture_stderr_separately' => true]
        );
    }

    protected function getCommandForOptionTest(): CommandTester
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $bulkCreateServiceProxyMock = self::createMock(BulkCreateUsersServiceConsoleProxy::class);
        $folderSyncServiceMock = self::createMock(FolderSyncService::class);

        $kernel->getContainer()->set(BulkCreateUsersServiceConsoleProxy::class, $bulkCreateServiceProxyMock);
        $kernel->getContainer()->set(FolderSyncService::class, $folderSyncServiceMock);

        return new CommandTester($application->find(BulkUserCreationCommand::NAME));
    }

    protected function getCommandForBasicPipeline(): CommandTester
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $bulkCreateServiceProxyMock = self::createMock(BulkCreateUsersServiceConsoleProxy::class);
        $bulkCreateServiceProxyMock
            ->expects(self::once())
            ->method('createUsers')
            ->willReturn(new UserCreationResult('test', ['test']));

        $folderSyncServiceMock = self::createMock(FolderSyncService::class);
        $folderSyncServiceMock->expects(self::once())->method('sync');

        $kernel->getContainer()->set(BulkCreateUsersServiceConsoleProxy::class, $bulkCreateServiceProxyMock);
        $kernel->getContainer()->set(FolderSyncService::class, $folderSyncServiceMock);

        return new CommandTester($application->find(BulkUserCreationCommand::NAME));
    }
}
