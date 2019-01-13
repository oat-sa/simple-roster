<?php declare(strict_types=1);

namespace App\Model;

use App\Model\Validation\ValidationException;

abstract class AbstractModel
{
    /**
     * Throws prepared exception
     *
     * Why cannot it also do universal checks instead of checking a field existence outside?
     * Because we wand to address fields directly, not as strings
     *
     * @param string $fieldName
     * @throws ValidationException
     */
    protected function throwExceptionRequiredFieldEmpty(string $fieldName)
    {
        throw new ValidationException(sprintf('Required field "%s" isn\'t provided', $fieldName));
    }

    /**
     * Should throw an exception if the model data isn't valid
     *
     * @throws ValidationException
     */
    abstract public function validate(): void;
}
