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

namespace OAT\SimpleRoster\Tests\Unit\Ingester\Registry;

use OAT\SimpleRoster\Ingester\Registry\IngesterSourceRegistry;
use OAT\SimpleRoster\Ingester\Source\IngesterSourceInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class IngesterSourceRegistryTest extends TestCase
{
    /** @var IngesterSourceRegistry */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new IngesterSourceRegistry();
    }

    public function testItIsConstructedEmpty(): void
    {
        self::assertEmpty($this->subject->all());
    }

    public function testItThrowsAnErrorWhenRetrievingAnInvalidIngesterSourceName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Ingester source named 'invalid' cannot be found.");

        $this->subject->get('invalid');
    }

    public function testItCanAddAnRetrieveIngesterSources(): void
    {
        $source1 = $this->createMock(IngesterSourceInterface::class);
        $source2 = $this->createMock(IngesterSourceInterface::class);

        $source1->expects(self::once())->method('getRegistryItemName')->willReturn('sourceName1');
        $source2->expects(self::once())->method('getRegistryItemName')->willReturn('sourceName2');

        $this->subject
            ->add($source1)
            ->add($source2);

        self::assertCount(2, $this->subject->all());
        self::assertSame($source1, $this->subject->get('sourceName1'));
        self::assertSame($source2, $this->subject->get('sourceName2'));
    }
}
