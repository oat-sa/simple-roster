<?php

namespace App\Command\Ingesting\Exception;

class S3AccessException extends IngestingException
{
    public function __construct()
    {
        parent::__construct('Can not read file on AWS S3');
    }
}