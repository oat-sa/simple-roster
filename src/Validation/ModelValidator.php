<?php declare(strict_types=1);

namespace App\Validation;

use App\Model\ModelInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ModelValidator
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
    public function validate(ModelInterface $model): void
    {
        $violations = $this->validator->validate($model);

        if (count($violations)) {
            throw new ValidationException(sprintf('Validation failure: %s', (string)$violations));
        }
    }
}