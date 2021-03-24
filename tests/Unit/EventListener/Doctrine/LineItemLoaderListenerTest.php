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

namespace OAT\SimpleRoster\Tests\Unit\EventListener\Doctrine;

use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\EventListener\Doctrine\EntityListenerInterface;
use OAT\SimpleRoster\EventListener\Doctrine\LineItemLoaderListener;
use OAT\SimpleRoster\Repository\LineItemRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV6;

class LineItemLoaderListenerTest extends TestCase
{
    /** @var LineItemLoaderListener */
    private $subject;

    /** @var LineItemRepository|MockObject */
    private $lineItemRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lineItemRepository = $this->createMock(LineItemRepository::class);

        $this->subject = new LineItemLoaderListener($this->lineItemRepository);
    }

    public function testItIsEntityListener(): void
    {
        self::assertInstanceOf(EntityListenerInterface::class, $this->subject);
    }

    public function testItSetLineItemFromRepositoryOnPostLoadEvent(): void
    {
        $expectedLineItem = (new LineItem())->setId(new UuidV6('00000001-0000-6000-0000-000000000000'));

        $this->lineItemRepository
            ->expects(self::once())
            ->method('findOneById')
            ->with(new UuidV6('00000001-0000-6000-0000-000000000000'))
            ->willReturn($expectedLineItem);
        $assignment = (new Assignment())->setLineItem($expectedLineItem);

        $this->subject->postLoad($assignment);

        self::assertSame($expectedLineItem, $assignment->getLineItem());
    }
}
