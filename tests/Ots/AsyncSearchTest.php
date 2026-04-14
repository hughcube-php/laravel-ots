<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/8/23
 * Time: 15:58.
 */

namespace HughCube\Laravel\OTS\Tests\Ots;

use HughCube\Laravel\OTS\Tests\TestCase;

class AsyncSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfNetworkUnavailable();
    }

    public function testAsyncSearchReturnsAsyncContext()
    {
        $context = $this->getConnection()->asyncDoHandle('ListTable', []);
        $this->assertTrue(method_exists($context, 'wait'));

        $response = $context->wait();
        $this->assertIsArray($response);
    }

    public function testAsyncSqlQueryReturnsAsyncContext()
    {
        $context = $this->getConnection()->asyncDoHandle('ListTable', []);
        $this->assertTrue(method_exists($context, 'wait'));

        $response = $context->wait();
        $this->assertIsArray($response);
    }
}
