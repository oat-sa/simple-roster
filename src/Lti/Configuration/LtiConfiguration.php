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

namespace OAT\SimpleRoster\Lti\Configuration;

class LtiConfiguration
{
    /** @var string */
    private $ltiVersion;

    /** @var string */
    private $ltiLaunchPresentationReturnUrl;

    /** @var string */
    private $ltiLaunchPresentationLocale;

    /** @var string */
    private $ltiRegistrationId;

    public function __construct(
        string $ltiVersion,
        string $ltiLaunchPresentationReturnUrl,
        string $ltiLaunchPresentationLocale,
        string $ltiRegistrationId
    ) {
        $this->ltiVersion = $ltiVersion;
        $this->ltiLaunchPresentationReturnUrl = $ltiLaunchPresentationReturnUrl;
        $this->ltiLaunchPresentationLocale = $ltiLaunchPresentationLocale;
        $this->ltiRegistrationId = $ltiRegistrationId;
    }

    public function getLtiVersion(): string
    {
        return $this->ltiVersion;
    }

    public function getLtiLaunchPresentationReturnUrl(): string
    {
        return $this->ltiLaunchPresentationReturnUrl;
    }

    public function getLtiLaunchPresentationLocale(): string
    {
        return $this->ltiLaunchPresentationLocale;
    }

    public function getLtiRegistrationId(): string
    {
        return $this->ltiRegistrationId;
    }
}
