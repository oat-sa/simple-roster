<?php declare(strict_types=1);

namespace App\Ingesting\RowToModelMapper;

use App\AssignmentIdGenerator;
use App\Model\ModelInterface;
use App\Model\Assignment;
use App\Model\User;

class UserRowToModelMapper extends AbstractRowToModelMapper
{
    /**
     * @var AssignmentIdGenerator
     */
    private $assignmentIdGenerator;

    public function __construct(AssignmentIdGenerator $assignmentIdGenerator)
    {
        $this->assignmentIdGenerator = $assignmentIdGenerator;
    }

    public function map(array $row, array $fieldNames): ModelInterface
    {
        $fieldValues = $this->mapFileLineByFieldNames($row, $fieldNames);

        // collect the remaining elements of line to the single 'assignment' field
        $fieldCount = count($fieldNames);
        $fieldValues['assignments'] = [];
        for ($i = $fieldCount; $i < count($row); $i++) {
            $fieldValues['assignments'][] = $row[$i];
        }

        $assignmentUris = $fieldValues['assignments'];
        $assignments = [];
        foreach ($assignmentUris as $assignmentUri) {
            $assignmentId = $this->assignmentIdGenerator->generate($assignments);
            $newAssignment = new Assignment($assignmentId, $assignmentUri);
            $assignments[] = $newAssignment;
        }
        unset($fieldValues['assignments']);

        /** @var User $user */
        $user = new User($fieldValues['username'], $fieldValues['password']);

        $user->addAssignments(...$assignments);

        return $user;
    }
}