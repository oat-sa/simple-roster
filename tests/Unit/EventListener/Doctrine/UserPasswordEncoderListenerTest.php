<?php declare(strict_types=1);

namespace App\Tests\Unit\EventListener\Doctrine;

use App\Entity\User;
use App\EventListener\Doctrine\UserPasswordEncoderListener;
use Doctrine\ORM\Event\LifecycleEventArgs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserPasswordEncoderListenerTest extends TestCase
{
    /** @var UserPasswordEncoderInterface */
    private $userPasswordEncoderMock;

    /** @var UserPasswordEncoderListener */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->userPasswordEncoderMock = $this->createMock(UserPasswordEncoderInterface::class);
        $this->subject = new UserPasswordEncoderListener($this->userPasswordEncoderMock);
    }

    public function testItDoesNothingIfTheUserPlainPasswordIsEmpty()
    {
        $entity = $this->createMock(User::class);

        $event = $this->createMock(LifecycleEventArgs::class);

        $this
            ->userPasswordEncoderMock
            ->expects($this->never())
            ->method('encodePassword');

        $entity
            ->expects($this->never())
            ->method('setPassword');

        $this->subject->prePersist($entity, $event);
    }

    public function testItCorrectlyUpdatesTheEncodedPassword()
    {
        $entity = new User();
        $entity->setPlainPassword('password');

        $event = $this->createMock(LifecycleEventArgs::class);

        $this
            ->userPasswordEncoderMock
            ->expects($this->once())
            ->method('encodePassword')
            ->with($entity, 'password')
            ->willReturn('encodedPassword');

        $this->subject->prePersist($entity, $event);

        $this->assertEquals(
            'encodedPassword',
            $entity->getPassword()
        );
    }
}
