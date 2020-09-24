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

namespace App\Tests\Integration\Ingester\Source;

use App\Ingester\Source\LocalCsvIngesterSource;
use PHPUnit\Framework\TestCase;

class LocalCsvIngesterSourceTest extends TestCase
{
    public function testGetContentWithDefaultDelimiter(): void
    {
        $subject = new LocalCsvIngesterSource();
        $subject->setPath(__DIR__ . '/../../../Resources/Ingester/Valid/lti-instances.csv');

        $output = $subject->getContent();

        foreach ($output as $row) {
            self::assertCount(4, $row);
            self::assertStringContainsString('infra', $row['label']);
            self::assertStringContainsString('http://infra', $row['ltiLink']);
            self::assertStringContainsString('key', $row['ltiKey']);
            self::assertStringContainsString('secret', $row['ltiSecret']);
        }
    }

    public function testGetContentWithOtherDelimiter(): void
    {
        $subject = new LocalCsvIngesterSource();
        $subject
            ->setPath(__DIR__ . '/../../../Resources/Ingester/Valid/lti-instances.csv')
            ->setDelimiter('|');

        $output = $subject->getContent();

        foreach ($output as $row) {
            self::assertCount(1, $row);
        }
    }

    public function testGetContentWithOtherCharset(): void
    {
        $subject = new LocalCsvIngesterSource();
        $subject
            ->setPath(__DIR__ . '/../../../Resources/Ingester/Valid/UTF-16LE-lti-instances.csv')
            ->setCharset('UTF-16LE');

        $output = $subject->getContent();

        foreach ($output as $row) {
            self::assertCount(4, $row);
            self::assertSame('ms', $row['label']);
            self::assertSame('https://itinv01exp.invalsi.taocloud.org', $row['ltiLink']);
            self::assertSame('key', $row['ltiKey']);
            self::assertSame('secret', $row['ltiSecret']);
        }
    }

    public function testContentIsCountable(): void
    {
        $subject = new LocalCsvIngesterSource();
        $subject->setPath(__DIR__ . '/../../../Resources/Ingester/Valid/lti-instances.csv');

        self::assertCount(3, $subject);
    }
}
