<?php

namespace App\Ingesting\Source;

use App\Ingesting\Exception\IngestingException;

abstract class AbstractSource
{
    protected $accessParameters = [];

    /**
     * @throws IngestingException
     * @return \Generator
     */
    abstract public function iterateThroughLines(): \Generator;

    public function setAccessParameter($name, $value): void
    {
        if (array_key_exists($name, $this->accessParameters)) {
            $this->accessParameters[$name] = $value;
        }
    }
}