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
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Lti\LoadBalancer;

use LogicException;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;

class LtiInstanceLoadBalancerFactory
{
    public const STRATEGY_USERNAME = 'username';
    public const STRATEGY_USER_GROUP_ID = 'userGroupId';

    private const VALID_LOAD_BALANCING_STRATEGIES = [
        self::STRATEGY_USERNAME,
        self::STRATEGY_USER_GROUP_ID,
    ];

    /** @var LtiInstanceRepository */
    private $ltiInstanceRepository;

    public function __construct(LtiInstanceRepository $ltiInstanceRepository)
    {
        $this->ltiInstanceRepository = $ltiInstanceRepository;
    }

    /**
     * @throws LogicException
     */
    public function __invoke(string $ltiLoadBalancingStrategy): LtiInstanceLoadBalancerInterface
    {
        switch ($ltiLoadBalancingStrategy) {
            case self::STRATEGY_USERNAME:
                return new UsernameLtiInstanceLoadBalancer($this->ltiInstanceRepository->findAllAsCollection());
            case self::STRATEGY_USER_GROUP_ID:
                return new UserGroupIdLtiInstanceLoadBalancer($this->ltiInstanceRepository->findAllAsCollection());
            default:
                throw new LogicException(
                    sprintf(
                        'Invalid load balancing strategy received. Possible values: %s',
                        implode(', ', self::VALID_LOAD_BALANCING_STRATEGIES)
                    )
                );
        }
    }
}
