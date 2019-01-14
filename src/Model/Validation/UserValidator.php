<?php declare(strict_types=1);

namespace App\Model\Validation;

use App\Model\ModelInterface;
use App\Model\User;
use Symfony\Component\Validator\Constraints;

class UserValidator extends AbstractModelValidator
{
    /**
     * @param ModelInterface $user
     * @throws ValidationException
     */
    public function validate(ModelInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }
        $violations = $this->validator->startContext()
            ->atPath('login')->validate($user->getLogin(), [
                new Constraints\NotBlank(),
            ])
            ->atPath('password')->validate($user->getPassword(), [
                new Constraints\NotBlank(),
            ])
            ->getViolations();

        $this->throwIfConstraintViolationsNotEmpty($violations);
    }
}