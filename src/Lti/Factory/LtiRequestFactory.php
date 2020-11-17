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

namespace OAT\SimpleRoster\Lti\Factory;

use LogicException;
use OAT\SimpleRoster\Lti\Request\LtiRequest;

class LtiRequestFactory
{
    /** @var string */
    private $ltiVersion;

    /** @var Lti1p1RequestFactory */
    private $lti1p1RequestFactory;

    /** @var Lti1p3RequestFactory */
    private $lti1p3RequestFactory;

    public function __construct(
        string $ltiVersion,
        Lti1p1RequestFactory $lti1p1RequestFactory,
        Lti1p3RequestFactory $lti1p3RequestFactory
    ) {
        $this->ltiVersion = $ltiVersion;
        $this->lti1p1RequestFactory = $lti1p1RequestFactory;
        $this->lti1p3RequestFactory = $lti1p3RequestFactory;
    }

    /**
     * @throws LogicException
     */
    public function __invoke(): LtiRequestFactoryInterface
    {
        switch ($this->ltiVersion) {
            case LtiRequest::LTI_VERSION_1P1:
                return $this->lti1p1RequestFactory;
            case LtiRequest::LTI_VERSION_1P3:
                return $this->lti1p3RequestFactory;
            default:
                throw new LogicException('Invalid LTI Version specified: ' . $this->ltiVersion);
        }
    }
}
