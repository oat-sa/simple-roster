<?php

namespace App\Model;

use App\Model\Validation\ValidationException;

abstract class AbstractModel
{
    /**
     * @param array $data
     * @return mixed
     */
    abstract static public function createFromArray(array $data): self;

    /**
     * @return array than can be saved and then passed to self::createFromArray to reproduce an entity
     */
    abstract public function toArray();

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
