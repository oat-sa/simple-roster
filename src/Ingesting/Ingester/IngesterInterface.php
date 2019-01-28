<?php

namespace App\Ingesting\Ingester;

use App\Ingesting\Source\SourceInterface;

interface IngesterInterface
{
    public const TYPE_USER_AND_ASSIGNMENT = 'users-assignments';
    public const TYPE_INFRASTRUCTURE = 'infrastructures';
    public const TYPE_LINE_ITEM = 'line-items';

    public function ingest(SourceInterface $source, bool $dryRun): array;

    public function getType(): string ;

    public function isUpdateMode(): bool;
}