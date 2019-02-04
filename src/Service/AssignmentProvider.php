<?php

namespace App\Service;

use App\Model\Assignment;
use App\Model\LineItem;
use App\Model\User;
use App\ODM\ItemManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Security;

class AssignmentProvider
{
    private $security;
    private $itemManager;

    public function __construct(Security $security, ItemManagerInterface $itemManager)
    {
        // Avoid calling getUser() in the constructor: auth may not
        // be complete yet. Instead, store the entire Security object.
        $this->security = $security;
        $this->itemManager = $itemManager;
    }

    public function getTakeableAssignments(): \Generator
    {
        foreach ($this->getUser()->getAssignments() as $assignment) {
            // cancelled assignment cannot be listed
            if ($assignment->isCancelled()) {
                continue;
            }

            yield $assignment;
        }
    }

    public function serializeAssignmentForListing(Assignment $assignment): array
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->itemManager->load(LineItem::class, $assignment->getLineItemTaoUri());

        if ($lineItem === null) {
            throw new NotFoundHttpException('Line item "'. $assignment->getLineItemTaoUri() .'" does not exist.');
        }

        return [
            'id' => $assignment->getId(),
            'username' => $this->getUser()->getUsername(),
            'state' => $assignment->getState(),
            'lineItem' => [
                'uri' => $lineItem->getTaoUri(),
                'label' => $lineItem->getLabel(),
                'startDateTime' => $lineItem->getStartDateTime() ? $lineItem->getStartDateTime()->getTimestamp() : '',
                'endDateTime' => $lineItem->getEndDateTime() ? $lineItem->getEndDateTime()->getTimestamp() : '',
                'infrastructure' => $lineItem->getInfrastructureId(),
            ],
        ];
    }

    public function getAssignmentsSerializedForListing(): array
    {
        $data = [];

        foreach ($this->getTakeableAssignments() as $assignment) {
            $data[] = $this->serializeAssignmentForListing($assignment);
        }

        return $data;
    }

    private function getUser(): User
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (null === $user) {
            throw new \RuntimeException('User is not logged in.');
        }

        return $user;
    }
}