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

namespace OAT\SimpleRoster\DataTransferObject;

class UserCreationResultMessage
{
    public function normalizeMessage(array $slugTotalUsers, array $userPrefix): string
    {
        $userPrefixString = implode(',', $userPrefix);

        return count($slugTotalUsers) > 1
            ? $this->multipleSlugNormalizeMessage($slugTotalUsers, $userPrefixString)
            : $this->singleSlugNormalizeMessage($slugTotalUsers, $userPrefixString);
    }

    private function singleSlugNormalizeMessage(array $slugTotalUsers, string $userPrefixString): string
    {
        return sprintf(
            '%s users created for line item %s for user prefix %s',
            reset($slugTotalUsers),
            array_key_first($slugTotalUsers),
            $userPrefixString
        );
    }

    private function multipleSlugNormalizeMessage(array $slugTotalUsers, string $userPrefixString): string
    {
        $message = '';
        foreach ($slugTotalUsers as $slug => $totalUsers) {
            $message .= sprintf(
                "%s users created for line item %s for user prefix %s \n",
                $totalUsers,
                $slug,
                $userPrefixString
            );
        }

        return $message;
    }
}
