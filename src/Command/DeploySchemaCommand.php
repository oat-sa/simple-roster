<?php declare(strict_types=1);

namespace App\Command;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeploySchemaCommand extends Command
{
    /**
     * @var DynamoDbClient
     */
    private $dynamoDbClient;

    /** @var SymfonyStyle */
    private $io;

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    public function __construct(DynamoDbClient $dynamoDbClient)
    {
        parent::__construct();

        $this->dynamoDbClient = $dynamoDbClient;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('tao:deploy:schema')
            ->setDescription('Deploys DynamoDB schema');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        try {
            $params = [
                'TableName' => 'infrastructures',
                'KeySchema' => [
                    [
                        'AttributeName' => 'id',
                        'KeyType' => 'HASH'
                    ]
                ],
                'AttributeDefinitions' => [
                    [
                        'AttributeName' => 'id',
                        'AttributeType' => 'S'
                    ]
                ],
                'ProvisionedThroughput' => [
                    'ReadCapacityUnits' => 10,
                    'WriteCapacityUnits' => 10
                ]
            ];
            $this->dynamoDbClient->createTable($params);

            $params = [
                'TableName' => 'line_items',
                'KeySchema' => [
                    [
                        'AttributeName' => 'taoUri',
                        'KeyType' => 'HASH'
                    ]
                ],
                'AttributeDefinitions' => [
                    [
                        'AttributeName' => 'taoUri',
                        'AttributeType' => 'S'
                    ]
                ],
                'ProvisionedThroughput' => [
                    'ReadCapacityUnits' => 10,
                    'WriteCapacityUnits' => 10
                ]
            ];
            $this->dynamoDbClient->createTable($params);

            $params = [
                'TableName' => 'users',
                'KeySchema' => [
                    [
                        'AttributeName' => 'login',
                        'KeyType' => 'HASH'
                    ]
                ],
                'AttributeDefinitions' => [
                    [
                        'AttributeName' => 'login',
                        'AttributeType' => 'S'
                    ]
                ],
                'ProvisionedThroughput' => [
                    'ReadCapacityUnits' => 10,
                    'WriteCapacityUnits' => 10
                ]
            ];
            $this->dynamoDbClient->createTable($params);
        } catch (DynamoDbException $e) {
            $this->io->error(sprintf('Unable to deploy schema: %s', $e->getMessage()));
        }

        $this->io->success(sprintf('Schema has been deployed successfully'));
    }
}