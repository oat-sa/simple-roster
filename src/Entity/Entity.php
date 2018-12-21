<?php

namespace App\Entity;

use App\Entity\Validation\ValidationException;

abstract class Entity
{
    protected $data = [];
    protected $requiredProperties = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    abstract public function getTable(): string;

    abstract public function getKey();

    /**
     * @throws \Exception
     */
    public function validate()
    {
        foreach ($this->requiredProperties as $requiredProperty) {
            if (!array_key_exists($requiredProperty, $this->data) || $this->data[$requiredProperty] === null || $this->data[$requiredProperty] === '') {
                throw new ValidationException(sprintf('Required field "%s" isn\'t provided', $requiredProperty));
            }
        }
    }

    public function getData(): array
    {
        return $this->data;
    }
}
