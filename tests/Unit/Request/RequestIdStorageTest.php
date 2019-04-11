<?php declare(strict_types=1);

namespace App\Tests\Unit\Request;

use App\Request\RequestIdStorage;
use LogicException;
use PHPUnit\Framework\TestCase;

class RequestIdStorageTest extends TestCase
{
    /** @var RequestIdStorage */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new RequestIdStorage();
    }

    public function testIfRequestIdCannotBeSetMoreThanOnce(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Request ID cannot not be set more than once per request.');

        $this->subject->setRequestId('test');
        $this->subject->setRequestId('test');
    }
}
