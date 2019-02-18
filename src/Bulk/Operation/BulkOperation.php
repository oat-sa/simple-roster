<?php declare(strict_types=1);

namespace App\Bulk\Operation;

class BulkOperation
{
    public const TYPE_CREATE = 'create';
    public const TYPE_UPDATE = 'update';

    /** @var string */
    private $identifier;

    /** @var string */
    private $type;

    /** @var string[] */
    private $attributes;

    /** @var array */
    private $context;

    public function __construct(string $identifier, string $type, array $attributes = [], array $context = [])
    {
        $this->identifier = $identifier;
        $this->type = $type;
        $this->attributes = $attributes;
        $this->context = $context;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $attributeName): string
    {
        return $this->attributes[$attributeName];
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
