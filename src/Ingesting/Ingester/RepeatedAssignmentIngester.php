<?php declare(strict_types=1);

namespace App\Ingesting\Ingester;

use App\Ingesting\Exception\EntityDoesNotExistException;
use App\Ingesting\RowToModelMapper\UserRowToModelMapper;
use App\Model\Assignment;
use App\Model\ModelInterface;
use App\Model\User;
use App\ModelManager\UserManager;
use App\Validation\UserToRepeatAssignmentValidator;

class RepeatedAssignmentIngester extends AbstractIngester
{
    /**
     * {@inheritdoc}
     */
    protected $updateMode = true;

    public function __construct(UserManager $modelStorage, UserRowToModelMapper $rowToModelMapper, UserToRepeatAssignmentValidator $validator)
    {
        parent::__construct($modelStorage, $rowToModelMapper, $validator);
    }

    /**
     * {@inheritdoc}
     *
     * @throws EntityDoesNotExistException
     */
    protected function convertRowToModel(array $row): ModelInterface
    {
        $login = $row[0];

        /** @var User $user */
        $user = $this->modelManager->read($login);
        if (!$user) {
            throw new EntityDoesNotExistException($login);
        }

        if (count($user->getAssignments())) {
            /** @var Assignment $assignment */
            $assignment = current($user->getAssignments());

            $newAssignment = new Assignment($assignment->getLineItemTaoUri(), Assignment::STATE_READY);

            $user->addAssignments($newAssignment);
        }

        return $user;
    }
}