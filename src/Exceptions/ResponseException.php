<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/27
 * Time: 18:20
 */

namespace HughCube\Laravel\OTS\Exceptions;

class ResponseException extends Exception
{
    /**
     * @var mixed
     */
    private $response;

    public function __construct($response, $message = '', $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }
}
