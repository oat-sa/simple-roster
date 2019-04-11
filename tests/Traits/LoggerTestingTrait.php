<?php declare(strict_types=1);

namespace App\Tests\Traits;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

trait LoggerTestingTrait
{
    /** @var TestHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->setUpTestLogHandler();
    }

    protected function setUpTestLogHandler(): void
    {
        static::ensureKernelTestCase();

        /** @var Logger $logger */
        $logger = static::$container->get(LoggerInterface::class);
        $this->handler = new TestHandler();

        $logger->pushHandler($this->handler);
    }

    public function getLogRecords(): array
    {
        return $this->handler->getRecords();
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

    public function assertHasRecordThatPasses(callable $callable, int $level): void
    {
        $this->assertTrue($this->handler->hasRecordThatPasses($callable, $level));
    }
}
