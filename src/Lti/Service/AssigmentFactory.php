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
 *  Copyright (c) 2022 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Lti\Service;

use Carbon\Carbon;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Model\AssignmentCollection;
use OAT\SimpleRoster\Model\UserCollection;

class AssigmentFactory implements AssigmentFactoryInterface
{
    public function fromUsersWithLineItem(UserCollection $users, LineItem $lineItem): AssignmentCollection
    {
        $res = [];
        foreach ($users as $user) {
            /** @var User $user */
            $model = new Assignment();
            $model->setUser($user);
            $model->setLineItem($lineItem);
            $model->setState(Assignment::STATE_READY);
            $model->setUpdatedAt(Carbon::now()->toDateTime());

            $res[] = $model;
        }

        return new AssignmentCollection($res);
    }
}