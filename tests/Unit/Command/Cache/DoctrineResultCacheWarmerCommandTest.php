<?php

declare(strict_types=1);

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

namespace App\Tests\Unit\Command\Cache;

use App\Command\Cache\DoctrineResultCacheWarmerCommand;
use App\Exception\DoctrineResultCacheImplementationNotFoundException;
use App\Generator\UserCacheIdGenerator;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DoctrineResultCacheWarmerCommandTest extends TestCase
{
    public function testItThrowsExceptionIfDoctrineResultCacheImplementationIsNotConfigured(): void
    {
        $this->expectException(DoctrineResultCacheImplementationNotFoundException::class);

        new DoctrineResultCacheWarmerCommand(
            $this->createMock(UserCacheIdGenerator::class),
            $this->createMock(Configuration::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(LoggerInterface::class)
        );
    }
}
