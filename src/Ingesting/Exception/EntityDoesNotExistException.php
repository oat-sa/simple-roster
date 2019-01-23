<?php declare(strict_types=1);

namespace App\Ingesting\Exception;

class EntityDoesNotExistException extends IngestingException
{
    public function __construct(string $entityId)
    {
        parent::__construct(sprintf('Model with primary key "%s" does not exist', $entityId));
    }
}