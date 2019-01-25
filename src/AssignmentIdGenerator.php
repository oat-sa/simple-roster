<?php declare(strict_types=1);

namespace App;

class AssignmentIdGenerator
{
    public function generate(array $existingAssignments): int
    {
        do {
            $exists = false;
            $generatedId = rand(1, pow(10, 5));
            foreach ($existingAssignments as $assignment) {
                if ($assignment->getId() === $generatedId) {
                    $exists = true;
                }
            }
        } while ($exists);

        return $generatedId;
    }
}
