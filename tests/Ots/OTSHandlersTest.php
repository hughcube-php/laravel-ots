<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/8/23
 * Time: 15:58.
 */

namespace HughCube\Laravel\OTS\Tests\Ots;

use Aliyun\OTS\Consts\QueryTypeConst;
use HughCube\Laravel\OTS\OTS\Handlers\OTSHandlers;
use HughCube\Laravel\OTS\OTS\Handlers\RequestContext;
use HughCube\Laravel\OTS\Tests\TestCase;

class OTSHandlersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfNetworkUnavailable();
    }

    protected function getOTSHandlers(): OTSHandlers
    {
        return new OTSHandlers($this->getConnection()->getOts()->handlers);
    }

    public function testDoHandle()
    {
        $handlers = $this->getOTSHandlers();

        $response = $handlers->doHandle('ListTable', []);
        $this->assertIsArray($response);
    }

    public function testAsyncDoHandle()
    {
        $handlers = $this->getOTSHandlers();

        $context = $handlers->asyncDoHandle('ListTable', []);
        $this->assertInstanceOf(RequestContext::class, $context);

        $response = $context->HWait();
        $this->assertIsArray($response);
    }

    public function testMagicGet()
    {
        $handlers = $this->getOTSHandlers();

        // Test accessing clientConfig
        $clientConfig = $handlers->clientConfig;
        $this->assertNotNull($clientConfig);

        // Test accessing httpClient
        $httpClient = $handlers->httpClient;
        $this->assertNotNull($httpClient);

        // Test accessing various handlers
        $this->assertNotNull($handlers->retryHandler);
        $this->assertNotNull($handlers->protoBufferDecoder);
        $this->assertNotNull($handlers->protoBufferEncoder);
        $this->assertNotNull($handlers->errorHandler);
        $this->assertNotNull($handlers->httpHeaderHandler);
        $this->assertNotNull($handlers->httpHandler);
    }
}
