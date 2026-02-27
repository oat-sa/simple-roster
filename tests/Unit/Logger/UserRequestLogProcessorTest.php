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

namespace OAT\SimpleRoster\Tests\Unit\Logger;

use Monolog\Level;
use Monolog\LogRecord;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Logger\UserRequestLogProcessor;
use OAT\SimpleRoster\Request\RequestIdStorage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class UserRequestLogProcessorTest extends TestCase
{
    private Security&MockObject $security;

    private RequestIdStorage $requestIdStorage;
    private UserRequestLogProcessor $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->security = $this->createMock(Security::class);
        $this->requestIdStorage = new RequestIdStorage();

        $this->subject = new UserRequestLogProcessor($this->security, $this->requestIdStorage);
    }

    private function createLogRecord(): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'test message',
            context: [],
            extra: []
        );
    }

    public function testItExtendsLogRecordWithRequestId(): void
    {
        $this->requestIdStorage->setRequestId('expectedRequestId');

        $record = $this->createLogRecord();
        $processed = ($this->subject)($record);

        self::assertArrayHasKey('requestId', $processed->extra);
        self::assertSame('expectedRequestId', $processed->extra['requestId']);
    }

    public function testItExtendsLogRecordWithUsername(): void
    {
        $this->security
            ->method('getUser')
            ->willReturn((new User())->setUsername('expectedUsername'));

        $record = $this->createLogRecord();
        $processed = ($this->subject)($record);

        self::assertArrayHasKey('username', $processed->extra);
        self::assertSame('expectedUsername', $processed->extra['username']);
    }

    public function testItExtendsLogRecordWithGuestUserIfUserCannotBeRetrieved(): void
    {
        $this->security
            ->method('getUser')
            ->willReturn(null);

        $record = $this->createLogRecord();
        $processed = ($this->subject)($record);

        self::assertArrayHasKey('username', $processed->extra);
        self::assertSame('guest', $processed->extra['username']);
    }
}
