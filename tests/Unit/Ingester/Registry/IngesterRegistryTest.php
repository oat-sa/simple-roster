<?php declare(strict_types=1);
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

namespace App\Tests\Unit\Ingester\Registry;

use App\Ingester\Ingester\IngesterInterface;
use App\Ingester\Registry\IngesterRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class IngesterRegistryTest extends TestCase
{
    /** @var IngesterRegistry */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new IngesterRegistry();
    }

    public function testItIsConstructedEmpty(): void
    {
        $this->assertEmpty($this->subject->all());
    }

    public function testItThrowsAnErrorWhenRetrievingAnInvalidIngesterName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Ingester named 'invalid' cannot be found.");

        $this->subject->get('invalid');
    }

    public function testItCanAddAnRetrieveIngesters(): void
    {
        $ingester1 = $this->createMock(IngesterInterface::class);
        $ingester2 = $this->createMock(IngesterInterface::class);

        $ingester1->expects($this->once())->method('getRegistryItemName')->willReturn('ingesterName1');
        $ingester2->expects($this->once())->method('getRegistryItemName')->willReturn('ingesterName2');

        $this->subject
            ->add($ingester1)
            ->add($ingester2);

        $this->assertCount(2, $this->subject->all());
        $this->assertSame($ingester1, $this->subject->get('ingesterName1'));
        $this->assertSame($ingester2, $this->subject->get('ingesterName2'));
    }
}
