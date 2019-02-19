<?php declare(strict_types=1);

namespace App\Tests\Unit\Logger;

use App\Entity\User;
use App\Logger\ExtendedLogProcessor;
use App\Request\RequestIdStorage;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;

class ExtendedLogProcessorTest extends TestCase
{
    /** @var Security|PHPUnit_Framework_MockObject_MockObject */
    private $security;

    /** @var SessionInterface|PHPUnit_Framework_MockObject_MockObject */
    private $session;

    /** @var RequestIdStorage */
    private $requestIdStorage;

    /** @var ExtendedLogProcessor */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->security = $this->createMock(Security::class);
        $this->session = $this->createMock(SessionInterface::class);
        $this->requestIdStorage = new RequestIdStorage();

        $this->subject = new ExtendedLogProcessor($this->security, $this->session, $this->requestIdStorage);
    }

    public function testItExtendsLogRecordWithRequestId(): void
    {
        $this->requestIdStorage->setRequestId('expectedRequestId');

        $logRecord = call_user_func_array($this->subject, [['logRecord']]);

        $this->assertArraySubset(['extra' => ['requestId' => 'expectedRequestId']], $logRecord);
    }

    public function testItExtendsLogRecordWithSessionId(): void
    {
        $this->session
            ->expects($this->once())
            ->method('getId')
            ->willReturn('expectedSessionId');

        $logRecord = call_user_func_array($this->subject, [['logRecord']]);

        $this->assertArraySubset(['extra' => ['sessionId' => 'expectedSessionId']], $logRecord);
    }

    public function testItExtendsLogRecordWithUsername(): void
    {
        $this->security
            ->method('getUser')
            ->willReturn((new User())->setUsername('expectedUsername'));

        $logRecord = call_user_func_array($this->subject, [['logRecord']]);

        $this->assertArraySubset(['extra' => ['username' => 'expectedUsername']], $logRecord);
    }

    public function testItExtendsLogRecordWithGuestUserIfUserCannotBeRetrieved(): void
    {
        $logRecord = call_user_func_array($this->subject, [['logRecord']]);

        $this->assertArraySubset(['extra' => ['username' => 'guest']], $logRecord);
    }
}
