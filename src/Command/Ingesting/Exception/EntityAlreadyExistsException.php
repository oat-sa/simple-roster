<?php

namespace App\Command\Ingesting\Exception;

class EntityAlreadyExistsException extends IngestingException
{
    public function __construct(string $entityId)
    {
        parent::__construct(sprintf('Entity with primary key "%s" already exists', $entityId));
    }
}