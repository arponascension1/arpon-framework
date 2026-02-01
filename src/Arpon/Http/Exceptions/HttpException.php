<?php

namespace Arpon\Http\Exceptions;

use Exception;
use Throwable;

class HttpException extends Exception
{
    /**
     * The status code for the exception.
     *
     * @var int
     */
    protected $statusCode;

    /**
     * The headers for the exception.
     *
     * @var array
     */
    protected $headers;

    /**
     * Create a new HTTP exception instance.
     *
     * @param  int  $statusCode
     * @param  string|null  $message
     * @param  \Throwable|null  $previous
     * @param  array  $headers
     * @param  int  $code
     * @return void
     */
    public function __construct($statusCode, $message = null, Throwable $previous = null, array $headers = [], $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message ?? '', $code, $previous);
    }

    /**
     * Get the status code.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Get the headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
