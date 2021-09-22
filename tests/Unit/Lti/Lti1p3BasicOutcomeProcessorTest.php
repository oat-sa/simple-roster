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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Lti;

use LogicException;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\SimpleRoster\Lti\BasicOutcome\Lti1p3BasicOutcomeProcessor;
use OAT\SimpleRoster\Service\CompleteUserAssignmentService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Lti1p3BasicOutcomeProcessorTest extends TestCase
{
    /** @var CompleteUserAssignmentService|MockObject */
    private $completeUserAssignmentService;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var Lti1p3BasicOutcomeProcessor */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->completeUserAssignmentService = $this->createMock(CompleteUserAssignmentService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new Lti1p3BasicOutcomeProcessor($this->completeUserAssignmentService, $this->logger);
    }

    public function testItDoesNotProcessBasicOutcomeReadResult(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Not supported basic outcome result type');

        $this->subject->processReadResult($this->createMock(RegistrationInterface::class), 'sourcedId');
    }

    public function testItDoesNotProcessBasicOutcomeDeleteResult(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Not supported basic outcome result type');

        $this->subject->processDeleteResult($this->createMock(RegistrationInterface::class), 'sourcedId');
    }

    public function testItCanProcessBasicOutcomeReplaceResult(): void
    {
        $this->completeUserAssignmentService
            ->expects(self::once())
            ->method('markAssignmentAsCompleted')
            ->with(10);

        $result = $this->subject->processReplaceResult($this->createMock(RegistrationInterface::class), '10', 0.23);

        self::assertTrue($result->isSuccess());
    }

    public function testItLogsUnsuccessfulBasicOutcomeReplaceResultProcessing(): void
    {
        $this->completeUserAssignmentService
            ->method('markAssignmentAsCompleted')
            ->willThrowException(new RuntimeException('Unexpected error'));

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with('Unsuccessful basic outcome replace result operation: Unexpected error');

        $result = $this->subject->processReplaceResult($this->createMock(RegistrationInterface::class), '10', 0.23);

        self::assertFalse($result->isSuccess());
    }
}
