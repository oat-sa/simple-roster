<?php declare(strict_types=1);

namespace App\Model\Validation;

use App\Model\ModelInterface;
use App\Model\Infrastructure;
use Symfony\Component\Validator\Constraints;

class InfrastructureValidator extends AbstractModelValidator
{
    /**
     * @param ModelInterface $infrastructure
     * @throws ValidationException
     */
    public function validate(ModelInterface $infrastructure): void
    {
        if (!$infrastructure instanceof Infrastructure) {
            return;
        }
        $violations = $this->validator->startContext()
            ->atPath('id')->validate($infrastructure->getId(), [
                new Constraints\NotBlank(),
            ])
            ->atPath('lti_director_link')->validate($infrastructure->getLtiDirectorLink(), [
                new Constraints\NotBlank(),
            ])
            ->atPath('key')->validate($infrastructure->getKey(), [
                new Constraints\NotBlank(),
            ])
            ->atPath('secret')->validate($infrastructure->getSecret(), [
                new Constraints\NotBlank(),
            ])
            ->getViolations();

        $this->throwIfConstraintViolationsNotEmpty($violations);
    }
}