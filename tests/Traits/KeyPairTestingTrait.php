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

namespace OAT\SimpleRoster\Tests\Traits;

/**
 * KeyPairTestingTrait
 *
 * @package OAT\SimpleRoster\Tests\Traits
 */
trait KeyPairTestingTrait
{
    protected function generateKeyPairs(): void
    {
        mkdir('tests/Resources/secrets');

        shell_exec('openssl genrsa -out tests/Resources/secrets/private.key 2048 2> /dev/null');
        shell_exec('openssl rsa -in tests/Resources/secrets/private.key -outform PEM' .
            ' -pubout -out tests/Resources/secrets/public.key 2> /dev/null');
    }

    protected function removeKeyPairs(): void
    {
        unlink('tests/Resources/secrets/private.key');
        unlink('tests/Resources/secrets/public.key');
        rmdir('tests/Resources/secrets');
    }
}