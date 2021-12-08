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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Csv;

use OAT\SimpleRoster\Csv\BulkUserCreationCsvWriter;
use OAT\SimpleRoster\Csv\CsvWriter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BulkUserCreationCsvWriterTest extends TestCase
{
    /** @var CsvWriter|MockObject */
    private $csvWriter;

    /** @var BulkUserCreationCsvWriter */
    private BulkUserCreationCsvWriter $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->csvWriter = $this->createMock(CsvWriter::class);

        $this->subject = new BulkUserCreationCsvWriter($this->csvWriter);
    }

    public function testIfCreateUserAssignmentCsv(): void
    {
        $csvHead = ['username', 'password', 'groupId'];
        $assignmentCsvHead = ['username', 'lineItemSlug'];

        $csvUserContent = [
            ['user_1', 'password_1', 'Group_1'],
            ['user_2', 'password_2', 'Group_1']
        ];

        $csvAssignmentContent = [
            ['user_1', 'lineItemSlug1'],
            ['user_2', 'lineItemSlug2']
        ];

        $this->csvWriter
            ->expects(self::exactly(4))
            ->method('writeCsv')
            ->withConsecutive(
                ['testPath/testPrefixPath/testCsvName.csv', $csvHead, $csvUserContent],
                ['testPath/testPrefixPath/Assignments-testSlugs-testPrefix.csv',
                    $assignmentCsvHead,
                    $csvAssignmentContent
                ],
                ['testPath/users_aggregated.csv', $csvHead, $csvUserContent],
                ['testPath/assignments_aggregated.csv', $assignmentCsvHead, $csvAssignmentContent],
            );

        $this->subject
            ->writeCsvData(
                'testSlugs',
                'testPrefix',
                'testPath/testPrefixPath',
                'testCsvName.csv',
                'testPath',
                $csvUserContent,
                $csvAssignmentContent
            );
    }
}
