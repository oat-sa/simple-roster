<?php declare(strict_types=1);

namespace App\Ingesting\Exception;

class S3AccessException extends IngestingException
{
    public function __construct()
    {
        parent::__construct('Can not read file on AWS S3');
    }
}