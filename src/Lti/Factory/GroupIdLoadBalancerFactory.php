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

namespace OAT\SimpleRoster\Lti\Factory;

use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use OAT\SimpleRoster\Exception\LtiInstanceNotFoundException;

class GroupIdLoadBalancerFactory
{
    private LtiInstanceRepository $ltiInstanceRepository;

    public function __construct(
        LtiInstanceRepository $ltiInstanceRepository
    ) {
        $this->ltiInstanceRepository = $ltiInstanceRepository;
    }

    public function getLoadBalanceGroupID(string $groupPrefix): array
    {
        $totalInstances = $this->ltiInstanceRepository->findAllAsCollection()->count();
        if (empty($totalInstances)) {
            throw new LtiInstanceNotFoundException('No Lti instance were found in database.');
        }
        $targetId = 1;
        $groupIds = [];
        while ($targetId <= $totalInstances) {
            $randomNumber = substr(md5(random_bytes(10)), 0, 10);
            $groupIds[] = sprintf($groupPrefix . '_%s', $randomNumber);
            $targetId++;
        }
        return $groupIds;
    }
}
