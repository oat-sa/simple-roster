<?php declare(strict_types=1);

namespace App\ApiProblem;

use Symfony\Component\HttpFoundation\Response;

/**
 * Basic implementation of Problem Details for HTTP APIs RFC
 */
class HttpApiProblem implements ApiProblemInterface
{
    private $statusCode;
    private $type;
    private $title;
    private $detail = '';

    /**
     * Note: add new titles here, for example for validation
     */
    private static $titles = [];

    public function __construct($statusCode, $type = null)
    {
        $this->statusCode = $statusCode;

        if ($type === null) {
            // no type? The default is about:blank and the title should
            // be the standard status code message
            $this->type = 'about:blank';
            $this->title = Response::$statusTexts[$statusCode] ?? 'Unknown status code :(';
        } else {
            if (!isset(self::$titles[$type])) {
                throw new \InvalidArgumentException('No title for type '. $type);
            }

            $this->title = self::$titles[$type];
        }
    }

    public function toArray(): array
    {
        return [
            'status' => $this->statusCode,
            'type' => $this->type,
            'title' => $this->title,
            'detail' => $this->detail,
        ];
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setDetail(string $detail): ApiProblemInterface
    {
        $this->detail = $detail;

        return $this;
    }
}