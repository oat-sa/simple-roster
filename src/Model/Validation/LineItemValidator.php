<?php declare(strict_types=1);

namespace App\Model\Validation;

use App\Model\ModelInterface;
use App\Model\LineItem;
use App\Model\Storage\InfrastructureStorage;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LineItemValidator extends AbstractModelValidator
{
    /**
     * @var InfrastructureStorage
     */
    private $infrastructureStorage;

    public function __construct(InfrastructureStorage $infrastructureStorage, ValidatorInterface $validator)
    {
        parent::__construct($validator);

        $this->infrastructureStorage = $infrastructureStorage;
    }

    /**
     * @param ModelInterface $lineItem
     * @throws ValidationException
     */
    public function validate(ModelInterface $lineItem): void
    {
        if (!$lineItem instanceof LineItem) {
            return;
        }
        $violations = $this->validator->startContext()
            ->atPath('tao_uri')->validate($lineItem->getTaoUri(), [
                new Constraints\NotBlank(),
                new Constraints\Url(),
            ])
            ->atPath('title')->validate($lineItem->getTitle(), [
                new Constraints\NotBlank(),
            ])
            ->atPath('infrastructure_id')->validate($lineItem->getInfrastructureId(), [
                new Constraints\NotBlank(),
            ])
            ->getViolations();

        $this->throwIfConstraintViolationsNotEmpty($violations);

        $infrastructureId = $lineItem->getInfrastructureId();

        $existingInfrastructure = $this->infrastructureStorage->read($infrastructureId);

        if ($existingInfrastructure === null) {
            throw new ValidationException(sprintf('Infrastructure with id "%s" not found', $infrastructureId));
        }
    }
}