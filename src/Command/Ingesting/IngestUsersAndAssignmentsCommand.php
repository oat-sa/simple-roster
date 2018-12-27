<?php

namespace App\Command\Ingesting;

use App\Entity\Entity;
use App\Entity\User;

class IngestUsersAndAssignmentsCommand extends AbstractIngestCommand
{
    /**
     * {@inheritdoc}
     */
    protected $updateMode = true;

    /**
     * @var string
     */
    protected $lastExistingUserLogin;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('tao:ingest:users-and-assignments')
            ->setDescription('Import a list of users and their assignments')
            ->setHelp($this->getHelpHeader('users and their assignments (TAO deliveries\' URIs)') . <<<'HELP'
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
HELP
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function getFields(): array
    {
        return ['login', 'password'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapFileLineByFieldNames(array $line): array
    {
        $fieldValues = parent::mapFileLineByFieldNames($line);

        // collect the remaining elements of line to the single 'assignment' field
        $fieldCount = count($this->getFields());
        $fieldValues['assignments'] = [];
        for ($i = $fieldCount; $i < count($line); $i++) {
            $fieldValues['assignments'][] = $line[$i];
        }

        return $fieldValues;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildEntity(array $fieldsValues): Entity
    {
        $assignments = $fieldsValues['assignments'];
        unset($fieldsValues['assignments']);
        $user = new User($fieldsValues);
        if (array_key_exists('login', $fieldsValues)) {
            $existingUser = $this->storage->read('users', ['login' => $fieldsValues['login']]);
            if ($existingUser) {
                $user = new User($existingUser);
                $this->lastExistingUserLogin = $user->getData()['login'];
            }
        }
        $user->addAssignments($assignments);

        return $user;
    }

    /**
     * Since we have already checked existence inside buildEntity,
     * let's prevent double query execution and just use the cache (lastExistingUserLogin).
     *
     * {@inheritdoc}
     */
    protected function checkIfExists(Entity $entity): bool
    {
        if ($this->lastExistingUserLogin === $entity->getData()['login']) {
            return true;
        }

        return parent::checkIfExists($entity);
    }
}