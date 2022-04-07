<?php

/*
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

namespace OAT\SimpleRoster\Tests\Unit\EventSubscriber;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use OAT\SimpleRoster\EventSubscriber\UserCacheInvalidationSubscriber;
use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\Generator\UserCacheIdGenerator;
use OAT\SimpleRoster\Service\Cache\UserCacheWarmerService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserCacheInvalidationSubscriberTest extends TestCase
{
    public function testPostFlushExceptionOnInvalidCacheDriver(): void
    {
        $subscriber = new UserCacheInvalidationSubscriber(
            self::createMock(UserCacheWarmerService::class),
            self::createMock(UserCacheIdGenerator::class),
            self::createMock(LoggerInterface::class)
        );

        $configurationMock = self::createMock(Configuration::class);
        $configurationMock->method('getResultCache')->willReturn(null);

        $entityManagerMock = self::createMock(EntityManagerInterface::class);
        $entityManagerMock->method('getConfiguration')->willReturn($configurationMock);

        self::expectException(DoctrineResultCacheImplementationNotFoundException::class);
        self::expectExceptionMessage('Doctrine result cache implementation is not configured.');
        $subscriber->postFlush(new PostFlushEventArgs($entityManagerMock));
    }
}
