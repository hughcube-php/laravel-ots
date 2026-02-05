<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/27
 * Time: 18:20.
 */

namespace HughCube\Laravel\OTS\Tests\Exceptions;

use Aliyun\OTS\OTSClientException;
use Exception;
use HughCube\Laravel\OTS\Exceptions\DropTableException;
use HughCube\Laravel\OTS\Exceptions\PartialSuccessResponseException;
use HughCube\Laravel\OTS\Exceptions\ResponseException;
use HughCube\Laravel\OTS\Tests\TestCase;

class ExceptionTest extends TestCase
{
    public function testExceptionInstanceOf()
    {
        $exception = new \HughCube\Laravel\OTS\Exceptions\Exception('Test message');
        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testExceptionMessage()
    {
        $exception = new \HughCube\Laravel\OTS\Exceptions\Exception('Test message');
        $this->assertSame('Test message', $exception->getMessage());
    }

    public function testExceptionCode()
    {
        $exception = new \HughCube\Laravel\OTS\Exceptions\Exception('Test message', 123);
        $this->assertSame(123, $exception->getCode());
    }

    public function testExceptionPrevious()
    {
        $previous = new Exception('Previous exception');
        $exception = new \HughCube\Laravel\OTS\Exceptions\Exception('Test message', 0, $previous);
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testResponseExceptionInstanceOf()
    {
        $exception = new ResponseException(['test' => 'response'], 'Test message');
        $this->assertInstanceOf(\HughCube\Laravel\OTS\Exceptions\Exception::class, $exception);
    }

    public function testResponseExceptionGetResponse()
    {
        $response = ['test' => 'response', 'data' => 123];
        $exception = new ResponseException($response, 'Test message');
        $this->assertSame($response, $exception->getResponse());
    }

    public function testResponseExceptionWithAllParameters()
    {
        $response = ['test' => 'response'];
        $previous = new Exception('Previous exception');
        $exception = new ResponseException($response, 'Test message', 456, $previous);

        $this->assertSame($response, $exception->getResponse());
        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(456, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testPartialSuccessResponseExceptionInstanceOf()
    {
        $exception = new PartialSuccessResponseException(['test' => 'response'], 'Partial success');
        $this->assertInstanceOf(ResponseException::class, $exception);
    }

    public function testPartialSuccessResponseExceptionGetResponse()
    {
        $response = ['tables' => [['rows' => [['is_ok' => false]]]]];
        $exception = new PartialSuccessResponseException($response, 'Partial success');
        $this->assertSame($response, $exception->getResponse());
    }

    public function testDropTableExceptionInstanceOf()
    {
        $exception = new DropTableException('Drop table failed');
        $this->assertInstanceOf(OTSClientException::class, $exception);
    }

    public function testDropTableExceptionMessage()
    {
        $exception = new DropTableException('Drop table failed');
        $this->assertSame('Drop table failed', $exception->getMessage());
    }
}
