<?php

namespace HughCube\Laravel\OTS\Tests\Stream;

use HughCube\Laravel\OTS\Stream\StreamReader;
use HughCube\Laravel\OTS\Tests\TestCase;

class StreamReaderTest extends TestCase
{
    public function testInstanceOf()
    {
        $reader = new StreamReader($this->getConnection(), 'test_table');
        $this->assertInstanceOf(StreamReader::class, $reader);
    }

    public function testStreamId()
    {
        $reader = new StreamReader($this->getConnection(), 'test_table');
        $result = $reader->streamId('test_stream_id');

        $this->assertSame($reader, $result);
    }

    public function testLimit()
    {
        $reader = new StreamReader($this->getConnection(), 'test_table');
        $result = $reader->limit(50);

        $this->assertSame($reader, $result);
    }

    public function testChainedMethods()
    {
        $reader = $this->getConnection()
            ->streamReader('test_table')
            ->streamId('test_id')
            ->limit(100);

        $this->assertInstanceOf(StreamReader::class, $reader);
    }
}
