<?php

namespace App\Command\Ingesting;

use App\Ingesting\RowToModelMapper\UserRowToModelMapper;
use App\Ingesting\Source\SourceFactory;
use App\Model\AbstractModel;
use App\Model\Storage\UserStorage;
use App\Model\User;
use App\S3\S3ClientFactory;

class IngestUsersAndAssignmentsCommand extends AbstractIngestCommand
{
    /**
     * {@inheritdoc}
     */
    protected $updateMode = true;

    public function __construct(UserStorage $modelStorage, S3ClientFactory $s3ClientFactory, SourceFactory $sourceFactory, UserRowToModelMapper $rowToModelMapper)
    {
        parent::__construct($modelStorage, $s3ClientFactory, $sourceFactory, $rowToModelMapper);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $help = <<<'HELP'
<options=bold>If there is a need to ingest assignments for an existing user, please follow the common pattern. 
User will NOT be recreated, the command will just add the new assignments to the user list. 
In this case you can even omit the password (just leave empty cell).</>

CSV fields: 
<info>user login</info> string <comment>must be unique</comment>
<info>user password</info> string <comment>plain</comment>
<info>assignment 1 line item tao URI</info> string <comment>optional</comment>
<info>assignment 2 line item tao URI</info> string <comment>optional</comment>
<info>assignment 3 line item tao URI</info> string <comment>optional</comment>
...
<info>assignment N line item tao URI</info> string <comment>optional</comment>

Example:
"Bob";"qwerty"
HELP;

        $this
            ->setName('tao:ingest:users-and-assignments')
            ->setDescription('Import a list of users and their assignments')
            ->setHelp($this->getHelpHeader('users and their assignments (TAO deliveries\' URIs)') . $help);

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function convertRowToModel(array $row): AbstractModel
    {
        return $this->rowToModelMapper->map($row, ['login', 'password'], User::class);
    }
}