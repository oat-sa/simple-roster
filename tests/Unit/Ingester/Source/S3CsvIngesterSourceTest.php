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

namespace App\Tests\Unit\Ingester\Source;

use App\Ingester\Source\S3CsvIngesterSource;
use Aws\S3\S3Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class S3CsvIngesterSourceTest extends TestCase
{
    /** @var S3CsvIngesterSource */
    private $subject;

    /** @var S3Client|MockObject */
    private $s3ClientMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->s3ClientMock = $this->createMock(S3Client::class);

        $this->subject = new S3CsvIngesterSource($this->s3ClientMock, 'bucket');
    }

    public function testRegistryItemName(): void
    {
        self::assertSame('s3', $this->subject->getRegistryItemName());
    }
}
