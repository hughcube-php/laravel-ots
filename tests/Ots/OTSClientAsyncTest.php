<?php

namespace HughCube\Laravel\OTS\Tests\Ots;

use Aliyun\OTS\AsyncResponse;
use HughCube\Laravel\OTS\Tests\TestCase;

class OTSClientAsyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfNetworkUnavailable();
    }

    public function testAsyncDoHandle()
    {
        $context = $this->getConnection()->getOts()->asyncDoHandle('ListTable', []);

        $this->assertInstanceOf(AsyncResponse::class, $context);
        $this->assertIsArray($context->wait());
    }

    public function testAsyncMethodsAreExposedBySdkClient()
    {
        $client = $this->getConnection()->getOts();

        $this->assertTrue(method_exists($client, 'asyncDoHandle'));
        $this->assertTrue(method_exists($client, 'asyncSearch'));
        $this->assertTrue(method_exists($client, 'asyncSqlQuery'));
    }
}
