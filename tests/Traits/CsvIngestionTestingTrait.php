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

namespace OAT\SimpleRoster\Tests\Traits;

use League\Csv\Writer;
use LogicException;
use OAT\SimpleRoster\Storage\StorageRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;

trait CsvIngestionTestingTrait
{
    /**
     * @throws LogicException
     */
    public function writeCsv(
        string $relativePath,
        array $csvContent,
        string $storageId = StorageRegistry::DEFAULT_STORAGE
    ): void {
        try {
            /** @var StorageRegistry $storageRegistry */
            $storageRegistry = self::getContainer()->get(StorageRegistry::class);

            $csv = Writer::createFromString();
            $csv->insertAll($csvContent);

            $storageRegistry->getFilesystem($storageId)->write($relativePath, $csv->getContent());
        } catch (Throwable $exception) {
            throw new LogicException(sprintf('Cannot write csv file: %s', $exception->getMessage()));
        }
    }

    /**
     * @throws LogicException
     */
    public function ingestCsv(string $commandName, string $relativePath): int
    {
        try {
            $application = $application = new Application(static::$kernel);

            return (new CommandTester($application->find($commandName)))->execute(
                [
                    'path' => $relativePath,
                    '--force' => true,
                ]
            );
        } catch (Throwable $exception) {
            throw new LogicException(sprintf('Cannot ingest csv file: %s', $exception->getMessage()));
        }
    }
}
