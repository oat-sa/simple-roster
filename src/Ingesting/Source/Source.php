<?php

namespace App\Ingesting\Source;

abstract class Source
{
    protected $accessParameters = [];

    abstract public function iterateThroughLines(): \Generator;

    public function setAccessParameter($name, $value): void
    {
        if (array_key_exists($name, $this->accessParameters)) {
            $this->accessParameters[$name] = $value;
        }
    }
}