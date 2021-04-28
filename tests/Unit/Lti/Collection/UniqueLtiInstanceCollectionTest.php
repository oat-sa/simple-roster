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

namespace OAT\SimpleRoster\Tests\Unit\Lti\Collection;

use OAT\SimpleRoster\Entity\LtiInstance;
use OAT\SimpleRoster\Lti\Collection\UniqueLtiInstanceCollection;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV6;

class UniqueLtiInstanceCollectionTest extends TestCase
{
    public function testItThrowsExceptionIfInvalidIndexReceived(): void
    {
        $ltiInstances = [
            new LtiInstance(new UuidV6('00000001-0000-6000-0000-000000000000'), 'instance_1', 'link', 'key', 'secret'),
            new LtiInstance(new UuidV6('00000002-0000-6000-0000-000000000000'), 'instance_2', 'link', 'key', 'secret'),
            new LtiInstance(new UuidV6('00000003-0000-6000-0000-000000000000'), 'instance_3', 'link', 'key', 'secret'),
            new LtiInstance(new UuidV6('00000004-0000-6000-0000-000000000000'), 'instance_4', 'link', 'key', 'secret'),
            new LtiInstance(new UuidV6('00000005-0000-6000-0000-000000000000'), 'instance_5', 'link', 'key', 'secret'),
        ];

        $subject = new UniqueLtiInstanceCollection(...$ltiInstances);

        foreach ([-2, -1, 5, 6] as $invalidIndex) {
            try {
                $subject->getByIndex($invalidIndex);

                self::fail(sprintf("Failed asserting that '%s' exception was thrown.", OutOfBoundsException::class));
            } catch (OutOfBoundsException $expectedException) {
                self::assertSame(
                    sprintf('Invalid index received: %d, possible range: 0..4', $invalidIndex),
                    $expectedException->getMessage()
                );
            }
        }
    }

    public function testIfLtiInstanceWithSameLabelCannotBeAddedTwice(): void
    {
        $ltiInstance = new LtiInstance(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'infra1',
            'link',
            'key',
            'secret'
        );

        $subject = new UniqueLtiInstanceCollection($ltiInstance, $ltiInstance);

        self::assertCount(1, $subject);

        $subject->add($ltiInstance);

        self::assertCount(1, $subject);
    }

    public function testIfCanBeFilteredByLtiKey(): void
    {
        $ltiInstances = [
            new LtiInstance(new UuidV6('00000001-0000-6000-0000-000000000000'), 'label1', 'link1', 'key1', 'secret1'),
            new LtiInstance(new UuidV6('00000002-0000-6000-0000-000000000000'), 'label2', 'link2', 'key1', 'secret2'),
            new LtiInstance(new UuidV6('00000003-0000-6000-0000-000000000000'), 'label3', 'link3', 'key1', 'secret3'),
            new LtiInstance(new UuidV6('00000004-0000-6000-0000-000000000000'), 'label4', 'link4', 'key2', 'secret4'),
            new LtiInstance(new UuidV6('00000005-0000-6000-0000-000000000000'), 'label5', 'link5', 'key2', 'secret5'),
        ];

        $subject = new UniqueLtiInstanceCollection(...$ltiInstances);

        self::assertCount(3, $subject->filterByLtiKey('key1'));
        self::assertSame('label1', $subject->getByIndex(0)->getLabel());
        self::assertSame('label2', $subject->getByIndex(1)->getLabel());
        self::assertSame('label3', $subject->getByIndex(2)->getLabel());
    }
}
