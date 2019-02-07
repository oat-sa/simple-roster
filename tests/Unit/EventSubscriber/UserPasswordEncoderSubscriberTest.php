<?php declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\UserPasswordEncoderSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserPasswordEncoderSubscriberTest extends TestCase
{
    /** @var UserPasswordEncoderInterface */
    private $userPasswordEncoderMock;

    /** @var UserPasswordEncoderSubscriber */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->userPasswordEncoderMock = $this->createMock(UserPasswordEncoderInterface::class);
        $this->subject = new UserPasswordEncoderSubscriber($this->userPasswordEncoderMock);
    }

    public function testPrePersistDoesNothingIfTheEntityIsNotInstanceOfUser()
    {
        $entity = new stdClass();

        $event = $this->createMock(LifecycleEventArgs::class);
        $event
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this
            ->userPasswordEncoderMock
            ->expects($this->never())
            ->method('encodePassword');

        $this->subject->prePersist($event);
    }

    public function testPreUpdateDoesNothingIfTheEntityIsNotInstanceOfUser()
    {
        $entity = new stdClass();

        $event = $this->createMock(LifecycleEventArgs::class);
        $event
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this
            ->userPasswordEncoderMock
            ->expects($this->never())
            ->method('encodePassword');

        $this->subject->preUpdate($event);
    }

    public function testItDoesNothingIfTheUserPlainPasswordIsEmpty()
    {
        $entity = $this->createMock(User::class);

        $event = $this->createMock(LifecycleEventArgs::class);
        $event
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this
            ->userPasswordEncoderMock
            ->expects($this->never())
            ->method('encodePassword');

        $entity
            ->expects($this->never())
            ->method('setPassword');

        $this->subject->prePersist($event);
    }

    public function testItCorrectlyUpdatesTheEncodedPassword()
    {
        $entity = new User();
        $entity->setPlainPassword('password');

        $event = $this->createMock(LifecycleEventArgs::class);
        $event
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this
            ->userPasswordEncoderMock
            ->expects($this->once())
            ->method('encodePassword')
            ->with($entity, 'password')
            ->willReturn('encodedPassword');

        $this->subject->prePersist($event);

        $this->assertEquals(
            'encodedPassword',
            $entity->getPassword()
        );
    }
}
