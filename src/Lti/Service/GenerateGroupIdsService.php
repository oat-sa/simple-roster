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

namespace OAT\SimpleRoster\Lti\Service;

use OAT\SimpleRoster\Entity\LtiInstance;
use OAT\SimpleRoster\Lti\Exception\LtiInstanceNotFoundException;
use OAT\SimpleRoster\Lti\Collection\UniqueLtiInstanceCollection;
use RuntimeException;

class GenerateGroupIdsService
{
    /**
     * @return string[]
     */
    public function generateGroupIds(
        string $groupPrefix,
        UniqueLtiInstanceCollection $ltiInstanceCollection
    ): array {
        $totalInstances = $ltiInstanceCollection->count();
        if ($totalInstances <= 0) {
            throw new LtiInstanceNotFoundException('No Lti instance were found in database.');
        }
        $groupIds = [];

        for ($index = 0; $index < $totalInstances; $index++) {
            do {
                $newGroupId = $this->getNewGroupId($groupPrefix);
                $possibleIndex = $ltiInstanceCollection->getByIndex(
                    $this->getHashIndex($newGroupId, $totalInstances)
                )->getId();

                if ($possibleIndex === null) {
                    throw new RuntimeException('Index cannot be null');
                }
            } while (array_key_exists($possibleIndex, $groupIds));

            $groupIds[$possibleIndex] = $newGroupId;
        }

        return array_values($groupIds);
    }

    private function getNewGroupId(string $groupPrefix): string
    {
        return sprintf($groupPrefix . '_%s', substr(md5(random_bytes(10)), 0, 10));
    }

    private function getHashIndex(string $groupId, int $size): int
    {
        $asciiSum = 0;
        $groupId = hash('md5', $groupId);
        for ($i = 0, $iMax = strlen($groupId); $i < $iMax; $i++) {
            $asciiSum += ord($groupId[$i]);
        }

        return $asciiSum % $size;
    }
}
