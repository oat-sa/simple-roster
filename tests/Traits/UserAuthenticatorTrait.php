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

namespace OAT\SimpleRoster\Tests\Traits;

use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Service\JWT\TokenIdGenerator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait UserAuthenticatorTrait
{
    protected function logInAs(User $user, KernelBrowser $kernelBrowser): string
    {
        $encodedParams = json_encode(array(
            'username' => $user->getUsername(),
            'password' => $user->getPlainPassword(),
        ));
        $kernelBrowser->request(
            'POST',
            '/api/v1/auth/token',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            $encodedParams ?: null
        );

        $data = json_decode($kernelBrowser->getResponse()->getContent(), true);

        $kernelBrowser->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data['accessToken']));

        return (string)$data['refreshToken'];
    }
}
