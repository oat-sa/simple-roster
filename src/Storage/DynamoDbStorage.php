<?php

namespace App\Storage;

use Aws\DynamoDb\Marshaler;

class DynamoDbStorage implements Storage
{
    /**
     * The key that DynamoDb uses to indicate the name of the table.
     */
    private const TABLE_NAME_KEY = 'TableName';

    /**
     * The key that DynamoDb uses to indicate whether or not to do a consistent read.
     */
    private const CONSISTENT_READ_KEY = 'ConsistentRead';

    /**
     * The key that is used to refer to the DynamoDb table key.
     */
    private const TABLE_KEY = 'Key';

    /**
     * The key that is used to refer to the marshaled item for DynamoDb table.
     */
    private const TABLE_ITEM_KEY = 'Item';

    /**
     * @var \Aws\DynamoDb\DynamoDbClient
     */
    private $client;

    /**
     * @var Marshaler
     */
    private $marshaler;

    public function __construct(\Aws\Sdk $awsSdk)
    {
        $this->client = $awsSdk->createDynamoDb();
        $this->marshaler = new Marshaler();
    }

    /**
     * @inheritdoc
     */
    public function read(string $tableName, array $keys): ?array
    {
        return null;
        $item = $this->client->getItem([
            self::TABLE_NAME_KEY => $tableName,
            self::CONSISTENT_READ_KEY => true,
            self::TABLE_KEY => $this->marshaler->marshalItem($keys),
        ]);
        if (!$item) {
            return null;
        }
        $item = $item->get(self::TABLE_ITEM_KEY);
        if ($item !== null) {
            $item = $this->marshaler->unmarshalItem($item);
        }
        return $item;
    }

    /**
     * @inheritdoc
     */
    public function insert(string $tableName, array $keys, array $data): void
    {
        $this->client->putItem([
            self::TABLE_NAME_KEY => $tableName,
            self::TABLE_ITEM_KEY => $this->marshaler->marshalItem($keys) + $this->marshaler->marshalItem($data),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function delete(string $storageName, array $keys): void
    {
        $this->client->deleteItem([
            self::TABLE_NAME_KEY => $storageName,
            self::TABLE_KEY => $this->marshaler->marshalItem($keys),
        ]);
    }
}