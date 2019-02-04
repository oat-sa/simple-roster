<?php

namespace App\Service;

use App\Model\Assignment;
use App\Model\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait AssignmentRetrieverTrait
{
    /**
     * @param User $user
     * @param string $id
     * @return Assignment
     * @throws NotFoundHttpException
     */
    public function getAssignmentById(User $user, string $id): Assignment
    {
        foreach ($user->getAssignments() as $assignment) {
            if ($assignment->getId() === $id) {
                return $assignment;
            }
        }

        throw new NotFoundHttpException('Assignment with id "' . $id . '" not found');
    }
}