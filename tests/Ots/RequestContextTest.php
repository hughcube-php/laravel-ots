<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/8/23
 * Time: 15:58.
 */

namespace HughCube\Laravel\OTS\Tests\Ots;

use HughCube\Laravel\OTS\OTS\Handlers\OTSHandlers;
use HughCube\Laravel\OTS\OTS\Handlers\RequestContext;
use HughCube\Laravel\OTS\Tests\TestCase;

class RequestContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfNetworkUnavailable();
    }

    public function testWithHPromise()
    {
        $context = $this->getConnection()->asyncDoHandle('ListTable', []);

        $this->assertInstanceOf(RequestContext::class, $context);

        // The context should already have a promise set
        $reflection = new \ReflectionClass($context);
        $property = $reflection->getProperty('hPromise');
        $property->setAccessible(true);
        $promise = $property->getValue($context);

        $this->assertNotNull($promise);
    }

    public function testWithHHandlers()
    {
        $context = $this->getConnection()->asyncDoHandle('ListTable', []);

        $reflection = new \ReflectionClass($context);
        $property = $reflection->getProperty('otsHandlers');
        $property->setAccessible(true);
        $handlers = $property->getValue($context);

        $this->assertInstanceOf(OTSHandlers::class, $handlers);
    }

    public function testHWait()
    {
        $context = $this->getConnection()->asyncDoHandle('ListTable', []);

        $response = $context->HWait();
        $this->assertIsArray($response);
    }

    public function testResponseReasonPhrase()
    {
        $context = $this->getConnection()->asyncDoHandle('ListTable', []);
        $context->HWait();

        $this->assertSame('OK', $context->responseReasonPhrase);
    }

    public function testResponseHttpStatus()
    {
        $context = $this->getConnection()->asyncDoHandle('ListTable', []);
        $context->HWait();

        $this->assertSame(200, $context->responseHttpStatus);
    }

    public function testResponseHeaders()
    {
        $context = $this->getConnection()->asyncDoHandle('ListTable', []);
        $context->HWait();

        $this->assertIsArray($context->responseHeaders);
        $this->assertNotEmpty($context->responseHeaders);
    }

    public function testDestructor()
    {
        // Create a context
        $context = $this->getConnection()->asyncDoHandle('ListTable', []);

        // Let the destructor handle waiting for the response
        unset($context);

        // If we get here without errors, the destructor worked
        $this->assertTrue(true);
    }

    public function testIsPendingBeforeWait()
    {
        $context = $this->getConnection()->asyncDoHandle('ListTable', []);

        $this->assertTrue($context->isPending());
        $this->assertFalse($context->isCompleted());
    }

    public function testIsCompletedAfterWait()
    {
        $context = $this->getConnection()->asyncDoHandle('ListTable', []);
        $context->HWait();

        $this->assertFalse($context->isPending());
        $this->assertTrue($context->isCompleted());
    }

    public function testSuccessful()
    {
        $context = $this->getConnection()->asyncDoHandle('ListTable', []);

        $this->assertTrue($context->successful());
        $this->assertFalse($context->failed());
    }

    public function testGetResponse()
    {
        $context = $this->getConnection()->asyncDoHandle('ListTable', []);

        $response = $context->getResponse();
        $this->assertIsArray($response);
    }

    public function testGetExceptionOnSuccess()
    {
        $context = $this->getConnection()->asyncDoHandle('ListTable', []);
        $context->HWait();

        $this->assertNull($context->getException());
    }

    public function testMultipleHWaitCallsReturnCachedResult()
    {
        $context = $this->getConnection()->asyncDoHandle('ListTable', []);

        $response1 = $context->HWait();
        $response2 = $context->HWait();

        // All calls should return the same cached result
        $this->assertSame($response1, $response2);
    }

    public function testStateAfterMultipleCalls()
    {
        $context = $this->getConnection()->asyncDoHandle('ListTable', []);

        // Call multiple times
        $context->HWait();
        $context->successful();
        $context->getResponse();

        // State should remain consistent
        $this->assertTrue($context->isCompleted());
        $this->assertTrue($context->successful());
        $this->assertFalse($context->failed());
        $this->assertNull($context->getException());
    }
}
