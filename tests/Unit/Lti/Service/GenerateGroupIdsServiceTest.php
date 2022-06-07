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

namespace OAT\SimpleRoster\Tests\Unit\Lti\Service;

use OAT\SimpleRoster\Entity\LtiInstance;
use OAT\SimpleRoster\Lti\Collection\UniqueLtiInstanceCollection;
use OAT\SimpleRoster\Lti\Exception\LtiInstanceNotFoundException;
use OAT\SimpleRoster\Lti\Service\GenerateGroupIdsService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class GenerateGroupIdsServiceTest extends TestCase
{
    public function testGenerateGroupIds(): void
    {
        $service = new GenerateGroupIdsService();
        $collection = new UniqueLtiInstanceCollection(
            new LtiInstance(1, 'test_label', 'test_link', 'test_key', 'test_secret')
        );

        $groupIds = $service->generateGroupIds('test', $collection);

        foreach ($groupIds as $groupId) {
            self::assertMatchesRegularExpression('~^test_[a-z0-9]+$~', $groupId);
        }
    }

    public function testGenerateGroupIdsExceptionOnEmptyCollection(): void
    {
        self::expectException(LtiInstanceNotFoundException::class);

        $service = new GenerateGroupIdsService();
        $collection = new UniqueLtiInstanceCollection();

        $service->generateGroupIds('test', $collection);
    }

    public function testGenerateGroupIdsExceptionOnInvalidIndex(): void
    {
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Index cannot be null');

        $service = new GenerateGroupIdsService();

        $mock = self::getMockBuilder(LtiInstance::class)
            ->setConstructorArgs([1, 'test_label', 'test_link', 'test_key', 'test_secret'])
            ->getMock();

        $mock->method('getId')->willReturn(null);

        /**@var LtiInstance $mock */
        $service->generateGroupIds('test', new UniqueLtiInstanceCollection($mock));
    }
}
