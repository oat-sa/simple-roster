<?php declare(strict_types=1);

namespace App\Tests\Traits;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

trait LoggerTestingTrait
{
    /** @var TestHandler */
    private $handler;

    protected function setUp()
    {
        $this->setUpTestLogHandler();
    }

    protected function setUpTestLogHandler(): void
    {
        /** @var Logger $logger */
        $logger = static::$container->get(LoggerInterface::class);
        $this->handler = new TestHandler();

        $logger->pushHandler($this->handler);
    }

    public function getLogRecords(): array
    {
        return $this->handler->getRecords();
    }

    public function assertHasEmergencyLogRecord(array $record): void
    {
        $this->assertHasLogRecord($record, Logger::EMERGENCY);
    }

    public function assertHasAlertLogRecord(array $record): void
    {
        $this->assertHasLogRecord($record, Logger::ALERT);
    }

    public function assertHasCriticalLogRecord(array $record): void
    {
        $this->assertHasLogRecord($record, Logger::CRITICAL);
    }

    public function assertHasErrorLogRecord(array $record): void
    {
        $this->assertHasLogRecord($record, Logger::ERROR);
    }

    public function assertHasWarningLogRecord(array $record): void
    {
        $this->assertHasLogRecord($record, Logger::WARNING);
    }

    public function assertHasNoticeLogRecord(array $record): void
    {
        $this->assertHasLogRecord($record, Logger::NOTICE);
    }

    public function assertHasInfoLogRecord(array $record): void
    {
        $this->assertHasLogRecord($record, Logger::INFO);
    }

    public function assertHasDebugLogRecord(array $record): void
    {
        $this->assertHasLogRecord($record, Logger::DEBUG);
    }

    public function assertHasEmergencyLogRecordWithMessage(string $message): void
    {
        $this->assertHasLogRecordWithMessage($message, Logger::EMERGENCY);
    }

    public function assertHasAlertLogRecordWithMessage(string $message): void
    {
        $this->assertHasLogRecordWithMessage($message, Logger::ALERT);
    }

    public function assertHasCriticalLogRecordWithMessage(string $message): void
    {
        $this->assertHasLogRecordWithMessage($message, Logger::CRITICAL);
    }

    public function assertHasErrorLogRecordWithMessage(string $message): void
    {
        $this->assertHasLogRecordWithMessage($message, Logger::ERROR);
    }

    public function assertHasWarningLogRecordWithMessage(string $message): void
    {
        $this->assertHasLogRecordWithMessage($message, Logger::WARNING);
    }

    public function assertHasNoticeLogRecordWithMessage(string $message): void
    {
        $this->assertHasLogRecordWithMessage($message, Logger::NOTICE);
    }

    public function assertHasInfoLogRecordWithMessage(string $message): void
    {
        $this->assertHasLogRecordWithMessage($message, Logger::INFO);
    }

    public function assertHasDebugLogRecordWithMessage(string $message): void
    {
        $this->assertHasLogRecordWithMessage($message, Logger::DEBUG);
    }

    public function assertHasLogRecord(array $record, int $level): void
    {
        $this->assertTrue(
            $this->handler->hasRecord($record, $level),
            sprintf(
                'Failed asserting that Logger contains record: [%s] %s',
                Logger::getLevelName($level),
                json_encode($record)
            )
        );
    }

    public function assertHasLogRecordWithMessage(string $message, int $level): void
    {
        $this->assertTrue(
            $this->handler->hasRecordThatContains($message, $level),
            sprintf(
                'Failed asserting that Logger contains record: [%s] %s',
                Logger::getLevelName($level),
                $message
            )
        );
    }
}
