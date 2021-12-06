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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Bulk;

use OAT\SimpleRoster\Csv\CsvWriter;

class BulkUserCreationCsvWriterService
{
    private CsvWriter $csvWriter;

    private array $userCsvHead = ['username','password','groupId'];
    private array $assignmentCsvHead = ['username','lineItemSlug'];

    public function __construct(
        CsvWriter $csvWriter
    ) {
        $this->csvWriter = $csvWriter;
    }

    public function writeCsvData(
        string $lineSlugs,
        string $prefix,
        string $csvPath,
        string $csvFilename,
        string $automateCsvPath,
        array $csvData,
        array $assignmentCsvData
    ): void {

        $this->csvWriter->writeCsv(sprintf('%s/%s', $csvPath, $csvFilename), $this->userCsvHead, $csvData);
        $this->csvWriter->writeCsv(
            sprintf('%s/Assignments-%s-%s.csv', $csvPath, $lineSlugs, $prefix),
            $this->assignmentCsvHead,
            $assignmentCsvData
        );
        $this->csvWriter->writeCsv(
            sprintf('%s/users_aggregated.csv', $automateCsvPath),
            $this->userCsvHead,
            $csvData
        );
        $this->csvWriter->writeCsv(
            sprintf('%s/assignments_aggregated.csv', $automateCsvPath),
            $this->assignmentCsvHead,
            $assignmentCsvData
        );
    }
}
