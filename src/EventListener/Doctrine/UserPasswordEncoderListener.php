<?php declare(strict_types=1);

namespace App\EventListener\Doctrine;

use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserPasswordEncoderListener implements EntityListenerInterface
{
    /** @var UserPasswordEncoderInterface */
    private $userPasswordEncoder;

    public function __construct(UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    public function prePersist(User $user): void
    {
        $this->encodeUserPassword($user);
    }

    public function preUpdate(User $user): void
    {
        $this->encodeUserPassword($user);
    }

    private function encodeUserPassword(User $user): void
    {
        if (!empty($user->getPlainPassword())) {
            $user->setPassword($this->userPasswordEncoder->encodePassword($user, $user->getPlainPassword()));
        }
    }
}
