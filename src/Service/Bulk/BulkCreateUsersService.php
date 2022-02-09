<?php

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Bulk;

use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Lti\Model\AutogeneratedUser;
use OAT\SimpleRoster\Lti\Service\AssigmentFactoryInterface;
use OAT\SimpleRoster\Lti\Service\GroupResolverInterface;
use OAT\SimpleRoster\Lti\Service\StateDrivenUserGenerator;
use OAT\SimpleRoster\Model\AssignmentCollection;
use OAT\SimpleRoster\Model\UserCollection;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Repository\Criteria\FindUserCriteria;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Storage\UserGenerator\StorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class BulkCreateUsersService
{
    private AssignmentRepository $assignmentRepository;
    private AssigmentFactoryInterface $assigmentFactory;
    private UserRepository $userRepository;
    private StorageInterface $storage;
    private UserPasswordEncoderInterface $hasher;

    private const DEFAULT_USERNAME_INCREMENT_VALUE = 0;

    public function __construct(
        AssignmentRepository         $assignmentRepository,
        UserRepository               $userRepository,
        AssigmentFactoryInterface    $assigmentFactory,
        StorageInterface             $storage,
        UserPasswordEncoderInterface $hasher
    )
    {
        $this->assignmentRepository = $assignmentRepository;
        $this->assigmentFactory = $assigmentFactory;
        $this->storage = $storage;
        $this->userRepository = $userRepository;
        $this->hasher = $hasher;
    }

    /**
     * @param LineItem[] $lineItems
     * @param string[] $userPrefixes
     * @param GroupResolverInterface|null $groupResolver
     */
    public function generate(
        array                   $lineItems,
        array                   $userPrefixes,
        int                     $batchSize,
        ?GroupResolverInterface $groupResolver = null
    ): void
    {
        $data = date('Y-m-d');
        $userNameLastIndexes = $this->getLastUserAssignedToLineItems($lineItems);

        foreach ($userPrefixes as $prefix) {
            $csvPath = sprintf('%s/%s', $data, $prefix);

            foreach ($lineItems as $lineItem) {
                $slug = $lineItem->getSlug();

                $csvFilename = sprintf('%s-%s.csv', $slug, $prefix);

                $generator = new StateDrivenUserGenerator(
                    $slug,
                    $prefix,
                    (int)$userNameLastIndexes[$slug] + 1,
                    $groupResolver
                );

                $users = $this->persistUsers($generatedUsers = $generator->makeBatch($batchSize));

                $assignments = $this->assigmentFactory->fromUsersWithLineItem($users, $lineItem);

                $this->persistAssignment($assignments);

                $this->storage->persistUsers(sprintf('%s/%s', $csvPath, $csvFilename), $generatedUsers);
                $this->storage->persistAssignments(sprintf('%s/Assignments-%s.csv', $csvPath, $csvFilename), $assignments);

                $this->storage->persistUsers(sprintf('%s/users_aggregated.csv', $data), $generatedUsers);
                $this->storage->persistAssignments(sprintf('%s/assignments_aggregated.csv', $data), $assignments);
            }
        }
    }

    /**
     * @param AutogeneratedUser[] $users
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function persistUsers(array $users): UserCollection
    {
        $usernames = [];
        foreach ($users as $user) {
            $this->userRepository->persist($user->toEntity($this->hasher));
            $usernames[] = $user->getName();
        }

        $this->userRepository->flush();

        return $this->userRepository->findAllByCriteria(
            (new FindUserCriteria())->addUsernameCriterion(...$usernames)
        );
    }

    protected function persistAssignment(AssignmentCollection $assignments): void
    {
        foreach ($assignments as $assignment) {
            $this->assignmentRepository->persist($assignment);
        }

        $this->assignmentRepository->flush();
    }

    /**
     * @param LineItem[] $lineItems
     */
    private function getLastUserAssignedToLineItems(array $lineItems): array
    {
        /*
         * TODO: replace with uuid or something similar? current solution is not consistency safe
         * */

        $userNameIncrementArray = [];

        foreach ($lineItems as $lineItem) {
            $assignment = $this->assignmentRepository->findByLineItemId((int)$lineItem->getId());
            $userNameIncrementArray[$lineItem->getSlug()] = self::DEFAULT_USERNAME_INCREMENT_VALUE;

            if (!empty($assignment)) {
                $userData = $assignment->getUser()->getUsername();
                $userNameArray = explode('_', (string)$userData);
                $index = end($userNameArray);
                if (count($userNameArray) > 0 && is_numeric($index)) {
                    $userNameIncrementArray[$lineItem->getSlug()] = (int)$index;
                }
            }
        }

        return $userNameIncrementArray;
    }
}