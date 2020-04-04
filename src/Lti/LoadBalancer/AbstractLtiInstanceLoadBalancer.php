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

namespace App\Lti\LoadBalancer;

/**
 * @see https://github.com/oat-sa/extension-tao-operations/blob/master/model/OperationUtils.php
 */
abstract class AbstractLtiInstanceLoadBalancer implements LtiInstanceLoadBalancerInterface
{
    /** @var string[] */
    private $ltiInstances;

    public function __construct(array $ltiInstances)
    {
        $this->ltiInstances = $ltiInstances;
    }

    protected function getLoadBalancedLtiInstanceUrl(string $value): string
    {
        $index = $this->asciiSum(hash('md5', $value)) % count($this->ltiInstances);

        return $this->ltiInstances[$index];
    }

    private function asciiSum(string $value): int
    {
        $asciiSum = 0;

        for ($i = 0, $iMax = strlen($value); $i < $iMax; $i++) {
            $asciiSum += ord($value[$i]);
        }

        return $asciiSum;
    }
}
