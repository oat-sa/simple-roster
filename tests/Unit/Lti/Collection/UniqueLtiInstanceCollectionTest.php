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

class UniqueLtiInstanceCollectionTest extends TestCase
{
    public function testItThrowsExceptionIfInvalidIndexReceived(): void
    {
        $ltiInstances = [
            new LtiInstance(1, 'instance_1', 'link', 'key', 'secret'),
            new LtiInstance(2, 'instance_2', 'link', 'key', 'secret'),
            new LtiInstance(3, 'instance_3', 'link', 'key', 'secret'),
            new LtiInstance(4, 'instance_4', 'link', 'key', 'secret'),
            new LtiInstance(5, 'instance_5', 'link', 'key', 'secret'),
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
        $ltiInstance = new LtiInstance(1, 'instance_1', 'link', 'key', 'secret');
        $subject = new UniqueLtiInstanceCollection($ltiInstance, $ltiInstance);

        self::assertCount(1, $subject);

        $subject->add($ltiInstance);

        self::assertCount(1, $subject);
    }

    public function testIfCanBeFilteredByLtiKey(): void
    {
        $ltiInstances = [
            new LtiInstance(1, 'label_1', 'link_1', 'key_1', 'secret_1'),
            new LtiInstance(2, 'label_2', 'link_2', 'key_1', 'secret_2'),
            new LtiInstance(3, 'label_3', 'link_3', 'key_1', 'secret_3'),
            new LtiInstance(4, 'label_4', 'link_4', 'key_2', 'secret_4'),
            new LtiInstance(5, 'label_5', 'link_5', 'key_2', 'secret_5'),
        ];

        $subject = new UniqueLtiInstanceCollection(...$ltiInstances);

        self::assertCount(3, $subject->filterByLtiKey('key_1'));
        self::assertSame('label_1', $subject->getByIndex(0)->getLabel());
        self::assertSame('label_2', $subject->getByIndex(1)->getLabel());
        self::assertSame('label_3', $subject->getByIndex(2)->getLabel());
    }
}
