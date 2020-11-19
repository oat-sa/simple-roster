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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\DependencyInjection\Compiler;

use OAT\SimpleRoster\DependencyInjection\Compiler\StoragePass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class StoragePassTest extends TestCase
{
    public function testItRegistersStorageDefinitionInContainer(): void
    {
        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder
            ->expects(self::once())
            ->method('findTaggedServiceIds')
            ->with('flysystem.storage')
            ->willReturn([
                'testService1' => ['testTag'],
                'testService2' => ['testTag'],
                'testService3' => ['testTag'],
            ]);

        $assertDefinitionCallback = static function (Definition $definition): bool {
            return $definition->hasTag('app.storage');
        };

        $containerBuilder
            ->expects(self::exactly(3))
            ->method('setDefinition')
            ->withConsecutive(
                [self::equalTo('app.storage.testService1'), self::callback($assertDefinitionCallback)],
                [self::equalTo('app.storage.testService2'), self::callback($assertDefinitionCallback)],
                [self::equalTo('app.storage.testService3'), self::callback($assertDefinitionCallback)],
            );

        (new StoragePass())->process($containerBuilder);
    }
}
