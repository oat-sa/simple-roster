<?php declare(strict_types=1);

namespace App\Tests\Unit\Logger;

use App\Entity\User;
use App\Logger\UserRequestSessionLogProcessor;
use App\Request\RequestIdStorage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;

class UserRequestSessionLogProcessorTest extends TestCase
{
    /** @var Security|MockObject */
    private $security;

    /** @var SessionInterface|MockObject */
    private $session;

    /** @var RequestIdStorage */
    private $requestIdStorage;

    /** @var UserRequestSessionLogProcessor */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->security = $this->createMock(Security::class);
        $this->session = $this->createMock(SessionInterface::class);
        $this->requestIdStorage = new RequestIdStorage();

        $this->subject = new UserRequestSessionLogProcessor($this->security, $this->session, $this->requestIdStorage);
    }

    public function testItExtendsLogRecordWithRequestId(): void
    {
        $this->requestIdStorage->setRequestId('expectedRequestId');

        $logRecord = call_user_func($this->subject, ['logRecord']);

        $this->assertArrayHasKey('extra', $logRecord);
        $this->assertArrayHasKey('requestId', $logRecord['extra']);
        $this->assertEquals('expectedRequestId', $logRecord['extra']['requestId']);
    }

    public function testItExtendsLogRecordWithSessionId(): void
    {
        $this->session
            ->expects($this->once())
            ->method('getId')
            ->willReturn('expectedSessionId');

        $logRecord = call_user_func($this->subject, ['logRecord']);

        $this->assertArrayHasKey('extra', $logRecord);
        $this->assertArrayHasKey('sessionId', $logRecord['extra']);
        $this->assertEquals('expectedSessionId', $logRecord['extra']['sessionId']);
    }

    public function testItExtendsLogRecordWithUsername(): void
    {
        $this->security
            ->method('getUser')
            ->willReturn((new User())->setUsername('expectedUsername'));

        $logRecord = call_user_func($this->subject, ['logRecord']);

        $this->assertArrayHasKey('extra', $logRecord);
        $this->assertArrayHasKey('username', $logRecord['extra']);
        $this->assertEquals('expectedUsername', $logRecord['extra']['username']);
    }

    public function testItExtendsLogRecordWithGuestUserIfUserCannotBeRetrieved(): void
    {
        $logRecord = call_user_func($this->subject, ['logRecord']);

        $this->assertArrayHasKey('extra', $logRecord);
        $this->assertArrayHasKey('username', $logRecord['extra']);
        $this->assertEquals('guest', $logRecord['extra']['username']);
    }
}
