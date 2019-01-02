<?php

namespace App\Ingesting\Exception;

class EntityAlreadyExistsException extends IngestingException
{
    public function __construct(string $entityId)
    {
        parent::__construct(sprintf('Model with primary key "%s" already exists', $entityId));
    }
}