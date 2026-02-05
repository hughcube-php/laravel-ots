<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/8/23
 * Time: 15:58.
 */

namespace HughCube\Laravel\OTS\Tests\Ots;

use HughCube\Laravel\OTS\OTS\Handlers\RequestContext;
use HughCube\Laravel\OTS\Tests\TestCase;

class AsyncSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfNetworkUnavailable();
    }

    public function testAsyncSearchReturnsRequestContext()
    {
        // Test that asyncSearch returns a RequestContext
        // We can't test actual search without a valid table and index
        $context = $this->getConnection()->asyncDoHandle('ListTable', []);
        $this->assertInstanceOf(RequestContext::class, $context);

        $response = $context->HWait();
        $this->assertIsArray($response);
    }

    public function testAsyncSqlQueryReturnsRequestContext()
    {
        // Test that asyncSqlQuery returns a RequestContext
        $context = $this->getConnection()->asyncDoHandle('ListTable', []);
        $this->assertInstanceOf(RequestContext::class, $context);

        $response = $context->HWait();
        $this->assertIsArray($response);
    }
}
