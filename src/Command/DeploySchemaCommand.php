<?php

namespace App\Command;

use Aws\DynamoDb\Exception\DynamoDbException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeploySchemaCommand extends Command
{
    /**
     * @var \Aws\Sdk
     */
    private $awsSdk;

    /** @var SymfonyStyle */
    protected $io;

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    public function __construct(\Aws\Sdk $awsSdk)
    {
        parent::__construct();

        $this->awsSdk = $awsSdk;
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
        $dynamodb = $this->awsSdk->createDynamoDb();

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
            $dynamodb->createTable($params);

            $params = [
                'TableName' => 'line_items',
                'KeySchema' => [
                    [
                        'AttributeName' => 'tao_uri',
                        'KeyType' => 'HASH'
                    ]
                ],
                'AttributeDefinitions' => [
                    [
                        'AttributeName' => 'tao_uri',
                        'AttributeType' => 'S'
                    ]
                ],
                'ProvisionedThroughput' => [
                    'ReadCapacityUnits' => 10,
                    'WriteCapacityUnits' => 10
                ]
            ];
            $dynamodb->createTable($params);

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
            $dynamodb->createTable($params);
        } catch (DynamoDbException $e) {
            $this->io->error(sprintf('Unable to deploy schema: %s', $e->getMessage()));
        }

        $this->io->success(sprintf('Schema has been deployed successfully'));
    }
}