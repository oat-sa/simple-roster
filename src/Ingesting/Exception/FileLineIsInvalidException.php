<?php declare(strict_types=1);

namespace App\Ingesting\Exception;

class FileLineIsInvalidException extends IngestingException
{
    private $lineNumber;

    public function __construct(int $lineNumber, ?string $message = '', ?int $code = null, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->lineNumber = $lineNumber;
    }

    public function getLineNumber()
    {
        return $this->lineNumber;
    }
}