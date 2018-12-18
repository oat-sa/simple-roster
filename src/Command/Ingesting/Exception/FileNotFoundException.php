<?php

namespace App\Command\Ingesting\Exception;

class FileNotFoundException extends IngestingException
{
    public function __construct(string $fileName)
    {
        parent::__construct(sprintf('Can not read file "%s"', $fileName));
    }
}