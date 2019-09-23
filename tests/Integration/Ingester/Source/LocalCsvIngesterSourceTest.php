<?php

declare(strict_types=1);

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

namespace App\Tests\Integration\Ingester\Source;

use App\Ingester\Source\LocalCsvIngesterSource;
use PHPUnit\Framework\TestCase;

class LocalCsvIngesterSourceTest extends TestCase
{
    public function testGetContentWithDefaultDelimiter(): void
    {
        $subject = new LocalCsvIngesterSource();
        $subject->setPath(__DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv');

        $output = $subject->getContent();

        foreach ($output as $row) {
            $this->assertCount(4, $row);
            $this->assertStringContainsString('infra', $row['label']);
            $this->assertStringContainsString('http://infra', $row['ltiDirectorLink']);
            $this->assertStringContainsString('key', $row['ltiKey']);
            $this->assertStringContainsString('secret', $row['ltiSecret']);
        }
    }

    public function testGetContentWithOtherDelimiter(): void
    {
        $subject = new LocalCsvIngesterSource();
        $subject
            ->setPath(__DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv')
            ->setDelimiter('|');

        $output = $subject->getContent();

        foreach ($output as $row) {
            $this->assertCount(1, $row);
        }
    }

    public function testGetContentWithOtherCharset(): void
    {
        $subject = new LocalCsvIngesterSource();
        $subject
            ->setPath(__DIR__ . '/../../../Resources/Ingester/Valid/UTF-16LE-infrastructures.csv')
            ->setCharset('UTF-16LE');

        $output = $subject->getContent();

        foreach ($output as $row) {
            $this->assertCount(4, $row);
            $this->assertEquals('ms', $row['label']);
            $this->assertEquals('https://itinv01exp.invalsi.taocloud.org', $row['ltiDirectorLink']);
            $this->assertEquals('key', $row['ltiKey']);
            $this->assertEquals('secret', $row['ltiSecret']);
        }
    }
}
