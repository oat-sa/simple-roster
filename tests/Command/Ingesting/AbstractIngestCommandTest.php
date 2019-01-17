<?php

namespace App\Tests\Command\Ingesting;

use App\Ingesting\Exception\InputOptionException;
use App\Ingesting\RowToModelMapper\RowToModelMapper;
use App\Ingesting\Source\SourceFactory;
use App\S3\InMemoryS3Client;
use App\S3\S3ClientFactory;
use App\Storage\InMemoryStorageInterface;
use App\Tests\Command\CommandTestCase;
use org\bovigo\vfs\vfsStream;

class AbstractIngestCommandTest extends CommandTestCase
{
    protected $fieldNames = ['name', 'mandatory_prop_1', 'mandatory_prop_2', 'optional_prop_1'];

    public function setUp()
    {
        vfsStream::setup('home');
    }

    public function testNotProvidedOptionsCauseException()
    {
        $command = new ConcretedIngestCommand(new ExampleStorage(new InMemoryStorageInterface()), new S3ClientFactory(InMemoryS3Client::class), new SourceFactory(), new RowToModelMapper());

        $this->expectException(InputOptionException::class);

        $input = $this->getInputMock();
        $output = $this->getOutputMock();

        $command->initialize($input, $output);
        $command->executeUnformatted($input);
    }

    public function testNotAllS3OptionsSpecified()
    {
        $command = new ConcretedIngestCommand(new ExampleStorage(new InMemoryStorageInterface()), new S3ClientFactory(InMemoryS3Client::class), new SourceFactory(), new RowToModelMapper());

        $this->expectException(InputOptionException::class);

        $input = $this->getInputMock(['s3_bucket' => 'bucket_name', 's3_region' => 'eu']);
        $output = $this->getOutputMock();

        $command->initialize($input, $output);
        $command->executeUnformatted($input);
    }

    public function testSimpleImportWithS3()
    {
        $bucketName = 'bucket_name';
        $objectName = 'object_name';

        $importedData = [
            ['name value', 'mandatory_prop_1 value', 'mandatory_prop_2 value', 'optional_prop_1 value',]
        ];

        $importedDataCsv = $this->convertArrayToCSV($importedData);

        $s3Client = new InMemoryS3Client();

        $s3Client->putObject($bucketName, $objectName, $importedDataCsv);

        $storage = new InMemoryStorageInterface();

        $command = new ConcretedIngestCommand(new ExampleStorage($storage), $this->getS3Factory($s3Client), new SourceFactory(), new RowToModelMapper());

        $input = $this->getInputMock([
            's3_region' => 'eu',
            's3_bucket' => $bucketName,
            's3_object' => $objectName,
            's3_access_key' => 'does not matter',
            's3_secret' => 'does not matter',
        ]);
        $output = $this->getOutputMock();

        $command->initialize($input, $output);
        $command->executeUnformatted($input);

        $savedEntity = $storage->read('example_table', ['name' => 'name value']);
        $this->assertSavedData($savedEntity, $importedData[0]);
    }

    public function testCsvStringsHavingDifferentAmountOfCells()
    {
        $importedData = [
            ['name value', 'mandatory_prop_1 value', 'mandatory_prop_2 value', 'optional_prop_1 value'],
            ['name value 2', 'mandatory_prop_1 value', 'mandatory_prop_2 value',],
        ];

        $importedDataCsv = $this->convertArrayToCSV($importedData);

        $filename = vfsStream::url('home/test.txt');
        file_put_contents($filename, $importedDataCsv);

        $storage = new InMemoryStorageInterface();

        $command = new ConcretedIngestCommand(new ExampleStorage($storage), $this->getS3Factory(), new SourceFactory(), new RowToModelMapper());

        $input = $this->getInputMock([
            'filename' => $filename,
            'delimiter' => ','
        ]);
        $output = $this->getOutputMock();

        $command->initialize($input, $output);
        $command->executeUnformatted($input);

        $savedEntity = $storage->read('example_table', ['name' => 'name value']);
        $this->assertSavedData($savedEntity, $importedData[0]);

        $savedEntity = $storage->read('example_table', ['name' => 'name value 2']);
        $this->assertSavedData($savedEntity, $importedData[1]);
    }

    protected function assertSavedData(array $savedEntity, array $importedData)
    {
        $i = 0;
        foreach ($this->fieldNames as $fieldName) {
            $this->assertEquals($importedData[$i] ?? null, $savedEntity[$fieldName]);
            $i++;
        }
    }

    private function getS3Factory($s3Client = null)
    {
        $s3Factory = $this->getMockBuilder(S3ClientFactory::class)->disableOriginalConstructor()->getMock();
        $s3Factory->expects($this->any())
            ->method('createClient')
            ->willReturn($s3Client);
        return $s3Factory;
    }

    private function convertArrayToCSV($data, $delimiter = ',', $enclosure = '"', $escape_char = "\\")
    {
        $f = fopen('php://memory', 'r+');
        foreach ($data as $item) {
            fputcsv($f, $item, $delimiter, $enclosure, $escape_char);
        }
        rewind($f);
        return stream_get_contents($f);
    }
}