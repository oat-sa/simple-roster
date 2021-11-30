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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\DataTransferObject;

use InvalidArgumentException;

class UserCreationResponse
{
    /** @var int */
    private const DEFAULT_SUCCESS_RESPONSE_VALUE = 0;

    private function getresponseMessage(array $slugTotalUsers, array $userPrefix): string
    {
        $responseMessage = '';
        $userPrefixString = implode(',', $userPrefix);
        foreach ($slugTotalUsers as $slugKey => $slugData) {
            $responseMessage .= sprintf(
                "%s users created for line item %s for user prefix %s \n",
                $slugData,
                $slugKey,
                $userPrefixString
            );
        }
        return $responseMessage;
    }

    public function userCreationResult(
        array $slugTotalUsers,
        array $notExistLineItems,
        array $userPrefix
    ): array {

        return [
            'message' => $this->getresponseMessage($slugTotalUsers, $userPrefix),
            'notExistLineItemsArray' => $notExistLineItems,
            'status' => self::DEFAULT_SUCCESS_RESPONSE_VALUE
        ];
    }
}
