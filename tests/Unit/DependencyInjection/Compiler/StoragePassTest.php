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
use OAT\SimpleRoster\Storage\Storage;
use Symfony\Component\DependencyInjection\Reference;

class StoragePassTest extends TestCase
{
    public function testItRegistersStorageDefinitionInContainer(): void
    {
        /** @var ContainerBuilder&MockObject $containerBuilder */
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        $containerBuilder
            ->expects(self::once())
            ->method('findTaggedServiceIds')
            ->with('flysystem.storage')
            ->willReturn([
                'testService1.storage' => ['testTag'],
                'testService2.storage' => ['testTag'],
                'testService3.storage' => ['testTag'],
            ]);

        $calls = [];

        $containerBuilder
            ->expects(self::exactly(3))
            ->method('setDefinition')
            ->willReturnCallback(function (string $id, Definition $definition) use (&$calls) {
                $calls[$id] = $definition;
                return $definition;
            });

        (new StoragePass())->process($containerBuilder);

        self::assertCount(3, $calls);

        foreach (
            [
                'app.storage.testService1' => ['storageId' => 'testService1', 'ref' => 'testService1.storage'],
                'app.storage.testService2' => ['storageId' => 'testService2', 'ref' => 'testService2.storage'],
                'app.storage.testService3' => ['storageId' => 'testService3', 'ref' => 'testService3.storage'],
            ] as $expectedServiceId => $expected
        ) {
            self::assertArrayHasKey($expectedServiceId, $calls);

            $definition = $calls[$expectedServiceId];
            self::assertSame(Storage::class, $definition->getClass());
            self::assertTrue($definition->hasTag('app.storage'));

            $args = $definition->getArguments();
            self::assertCount(2, $args);
            self::assertSame($expected['storageId'], $args[0]);

            self::assertInstanceOf(Reference::class, $args[1]);
            self::assertSame($expected['ref'], (string)$args[1]);
        }
    }

}
