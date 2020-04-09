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

namespace App\Tests\Unit\Model;

use App\Model\UsernameCollection;
use Countable;
use IteratorAggregate;
use PHPUnit\Framework\TestCase;

class UsernameCollectionTest extends TestCase
{
    public function testItImplementsCountable(): void
    {
        $this->assertInstanceOf(Countable::class, new UsernameCollection());
    }

    public function testItImplementsIteratorAggregate(): void
    {
        $this->assertInstanceOf(IteratorAggregate::class, new UsernameCollection());
    }

    public function testIfUsernameCanBeAdded(): void
    {
        $subject = (new UsernameCollection())->add('test');

        $this->assertCount(1, $subject);
        $this->assertSame('test', $subject->getIterator()->current());
    }
}
