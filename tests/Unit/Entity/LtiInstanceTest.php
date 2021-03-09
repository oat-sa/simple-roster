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

namespace OAT\SimpleRoster\Tests\Unit\Entity;

use Carbon\Carbon;
use OAT\SimpleRoster\Entity\EntityInterface;
use OAT\SimpleRoster\Entity\LtiInstance;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV4;

class LtiInstanceTest extends TestCase
{
    public function testItImplementsEntityInterface(): void
    {
        $id = new UuidV4('00000000-0000-4000-0000-000000000001');
        $subject = new LtiInstance($id, 'label', 'link', 'key', 'secret');

        self::assertInstanceOf(EntityInterface::class, $subject);
        self::assertSame($id, $subject->getId());
    }

    public function testItInitializesCreationTimeIfItIsNotProvided(): void
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(1615278391.261860)->toDateTimeString('microsecond'));

        $id = new UuidV4('00000000-0000-4000-0000-000000000001');
        $subject = new LtiInstance($id, 'label', 'link', 'key', 'secret');

        self::assertSame(1615278391261860, $subject->getCreatedAt()->getTimestamp());

        Carbon::setTestNow();
    }
}
