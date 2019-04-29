<?php declare(strict_types=1);

namespace App\Ingester\Source;

use Aws\S3\S3Client;
use League\Csv\Exception;
use League\Csv\Reader;
use Traversable;

class S3CsvIngesterSource extends AbstractIngesterSource
{
    /** @var S3Client */
    private $client;

    /** @var string  */
    private $bucket;

    public function __construct(S3Client $client, string $bucket)
    {
        $this->client = $client;
        $this->bucket = $bucket;
    }

    public function getRegistryItemName(): string
    {
        return 's3';
    }

    /**
     * @throws Exception
     */
    public function getContent(): Traversable
    {
        $result = $this->client->getObject([
            'Bucket' => $this->bucket,
            'Key'    => $this->path
        ]);

        $reader = Reader::createFromString((string)($result['Body'] ?? ''));

        $reader
            ->setDelimiter($this->delimiter)
            ->setHeaderOffset(0);

        if ($this->charset !== self::DEFAULT_CSV_CHARSET) {
            $reader->addStreamFilter(sprintf('convert.iconv.%s/UTF-8', $this->charset));
        }

        return $reader;
    }
}
