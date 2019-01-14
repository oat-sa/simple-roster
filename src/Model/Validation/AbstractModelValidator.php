<?php declare(strict_types=1);

namespace App\Model\Validation;

use App\Model\ModelInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractModelValidator
{
    protected $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param ModelInterface $model
     * @throws ValidationException
     */
    abstract public function validate(ModelInterface $model): void;

    /**
     * @param ConstraintViolationListInterface $constraintViolationList
     * @throws ValidationException
     */
    protected function throwIfConstraintViolationsNotEmpty(ConstraintViolationListInterface $constraintViolationList)
    {
        if (count($constraintViolationList)) {
            throw new ValidationException(sprintf('Validation failure: %s', (string)$constraintViolationList));
        }
    }
}