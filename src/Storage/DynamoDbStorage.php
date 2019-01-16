<?php declare(strict_types=1);

namespace App\Storage;

use Aws\DynamoDb\Marshaler;

class DynamoDbStorage implements StorageInterface
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

    public function __construct(\Aws\DynamoDb\DynamoDbClient $dynamoDbClient, Marshaler $marshaler)
    {
        $this->client = $dynamoDbClient;
        $this->marshaler = $marshaler;
    }

    /**
     * @inheritdoc
     */
    public function read(string $tableName, array $key): ?array
    {
        $item = $this->client->getItem([
            self::TABLE_NAME_KEY => $tableName,
            self::CONSISTENT_READ_KEY => true,
            self::TABLE_KEY => $this->marshaler->marshalItem($key),
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
    public function insert(string $tableName, array $key, array $data): void
    {
        $this->client->putItem([
            self::TABLE_NAME_KEY => $tableName,
            self::TABLE_ITEM_KEY => $this->marshaler->marshalItem($key) + $this->marshaler->marshalItem($data),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function delete(string $storageName, array $key): void
    {
        $this->client->deleteItem([
            self::TABLE_NAME_KEY => $storageName,
            self::TABLE_KEY => $this->marshaler->marshalItem($key),
        ]);
    }
}