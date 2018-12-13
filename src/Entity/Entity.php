<?php

namespace App\Entity;

abstract class Entity
{
    protected $data = [];
    protected $requiredProperties = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    abstract public function getTable(): string;

    /**
     * @todo multiple fields in key
     */
    abstract public function getKey();

    /**
     * @todo ValidatorException
     *
     * @throws \Exception
     */
    public function validate()
    {
        foreach ($this->requiredProperties as $requiredProperty) {
            if (!array_key_exists($requiredProperty, $this->data) || $this->data[$requiredProperty] === null || $this->data[$requiredProperty] === '') {
                throw new \Exception(sprintf('Required field "%s" isn\'t provided'));
            }
        }
    }

    public function getData(): array
    {
        return $this->data;
    }
}
