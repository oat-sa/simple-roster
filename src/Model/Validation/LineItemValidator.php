<?php declare(strict_types=1);

namespace App\Model\Validation;

use App\Model\ModelInterface;
use App\Model\LineItem;
use App\Model\Storage\InfrastructureManager;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LineItemValidator extends ModelValidator
{
    /**
     * @var InfrastructureManager
     */
    private $infrastructureStorage;

    public function __construct(InfrastructureManager $infrastructureStorage, ValidatorInterface $validator)
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
        parent::validate($lineItem);

        if (!$lineItem instanceof LineItem) {
            return;
        }

        $infrastructureId = $lineItem->getInfrastructureId();

        $existingInfrastructure = $this->infrastructureStorage->read($infrastructureId);

        if ($existingInfrastructure === null) {
            throw new ValidationException(sprintf('Infrastructure with id "%s" not found', $infrastructureId));
        }
    }
}