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

namespace App\Logger;

use App\Request\RequestIdStorage;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;

class UserRequestSessionLogProcessor
{
    /** @var Security */
    private $security;

    /** @var SessionInterface */
    private $session;

    /** @var RequestIdStorage */
    private $requestIdStorage;

    public function __construct(
        Security $security,
        SessionInterface $session,
        RequestIdStorage $requestIdStorage
    ) {
        $this->security = $security;
        $this->session = $session;
        $this->requestIdStorage = $requestIdStorage;
    }

    public function __invoke(array $record): array
    {
        $record['extra']['requestId'] = $this->requestIdStorage->getRequestId();
        $record['extra']['sessionId'] = $this->session->getId();
        $record['extra']['username'] = $this->security->getUser() !== null
            ? $this->security->getUser()->getUsername()
            : 'guest';

        return $record;
    }
}
