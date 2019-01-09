<?php

namespace App\Ingesting\Source;

use App\Ingesting\Exception\IngestingException;

interface SourceInterface
{
    /**
     * @throws IngestingException
     * @return \Generator
     */
    public function iterateThroughLines(): \Generator;
}