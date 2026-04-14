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

/**
 * Tests for the laravel-ots fallback RequestContext (used when SDK lacks native async).
 */
class RequestContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfNetworkUnavailable();
    }

    /**
     * Create a RequestContext via the fallback OTSHandlers proxy, bypassing SDK native async.
     */
    protected function createFallbackContext(string $apiName = 'ListTable', array $request = []): RequestContext
    {
        $ots = $this->getConnection()->getOts();
        /** @phpstan-ignore-next-line */
        $handlers = method_exists($ots, 'getHandlers') ? $ots->getHandlers() : $ots->handlers;
        $proxy = new OTSHandlers($handlers);
        return $proxy->asyncDoHandle($apiName, $request);
    }

    public function testWithHPromise()
    {
        $context = $this->createFallbackContext();

        $this->assertInstanceOf(RequestContext::class, $context);

        $reflection = new \ReflectionClass($context);
        $property = $reflection->getProperty('hPromise');
        $property->setAccessible(true);
        $promise = $property->getValue($context);

        $this->assertNotNull($promise);
    }

    public function testWithHHandlers()
    {
        $context = $this->createFallbackContext();

        $reflection = new \ReflectionClass($context);
        $property = $reflection->getProperty('otsHandlers');
        $property->setAccessible(true);
        $handlers = $property->getValue($context);

        $this->assertInstanceOf(OTSHandlers::class, $handlers);
    }

    public function testWait()
    {
        $context = $this->createFallbackContext();

        $response = $context->wait();
        $this->assertIsArray($response);
    }

    public function testResponseReasonPhrase()
    {
        $context = $this->createFallbackContext();
        $context->wait();

        $this->assertSame('OK', $context->responseReasonPhrase);
    }

    public function testResponseHttpStatus()
    {
        $context = $this->createFallbackContext();
        $context->wait();

        $this->assertSame(200, $context->responseHttpStatus);
    }

    public function testResponseHeaders()
    {
        $context = $this->createFallbackContext();
        $context->wait();

        $this->assertIsArray($context->responseHeaders);
        $this->assertNotEmpty($context->responseHeaders);
    }

    public function testDestructor()
    {
        $context = $this->createFallbackContext();
        unset($context);

        $this->assertTrue(true);
    }

    public function testIsPendingBeforeWait()
    {
        $context = $this->createFallbackContext();

        $this->assertTrue($context->isPending());
        $this->assertFalse($context->isCompleted());
    }

    public function testIsCompletedAfterWait()
    {
        $context = $this->createFallbackContext();
        $context->wait();

        $this->assertFalse($context->isPending());
        $this->assertTrue($context->isCompleted());
    }

    public function testSuccessful()
    {
        $context = $this->createFallbackContext();

        $this->assertTrue($context->successful());
        $this->assertFalse($context->failed());
    }

    public function testGetResponse()
    {
        $context = $this->createFallbackContext();

        $response = $context->getResponse();
        $this->assertIsArray($response);
    }

    public function testGetExceptionOnSuccess()
    {
        $context = $this->createFallbackContext();
        $context->wait();

        $this->assertNull($context->getException());
    }

    public function testMultipleWaitCallsReturnCachedResult()
    {
        $context = $this->createFallbackContext();

        $response1 = $context->wait();
        $response2 = $context->wait();

        $this->assertSame($response1, $response2);
    }

    public function testStateAfterMultipleCalls()
    {
        $context = $this->createFallbackContext();

        $context->wait();
        $context->successful();
        $context->getResponse();

        $this->assertTrue($context->isCompleted());
        $this->assertTrue($context->successful());
        $this->assertFalse($context->failed());
        $this->assertNull($context->getException());
    }
}
