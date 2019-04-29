<?php declare(strict_types=1);

namespace App\Http\Exception;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RequestEntityTooLargeHttpException extends HttpException
{
    /**
     * @param string     $message  The internal exception message
     * @param Exception $previous The previous exception
     * @param int        $code     The internal exception code
     * @param array      $headers
     */
    public function __construct(string $message = null, Exception $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(Response::HTTP_REQUEST_ENTITY_TOO_LARGE, $message, $previous, $headers, $code);
    }
}
