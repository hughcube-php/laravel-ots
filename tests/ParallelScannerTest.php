<?php

namespace HughCube\Laravel\OTS\Tests;

use HughCube\Laravel\OTS\ParallelScanner;

class ParallelScannerTest extends TestCase
{
    public function testInstanceOf()
    {
        $scanner = new ParallelScanner($this->getConnection(), 'test_table', 'test_index');
        $this->assertInstanceOf(ParallelScanner::class, $scanner);
    }

    public function testLimit()
    {
        $scanner = new ParallelScanner($this->getConnection(), 'test_table', 'test_index');
        $result = $scanner->limit(50);

        $this->assertSame($scanner, $result);
    }

    public function testAliveTime()
    {
        $scanner = new ParallelScanner($this->getConnection(), 'test_table', 'test_index');
        $result = $scanner->aliveTime(60);

        $this->assertSame($scanner, $result);
    }

    public function testColumns()
    {
        $scanner = new ParallelScanner($this->getConnection(), 'test_table', 'test_index');
        $result = $scanner->columns(['col1', 'col2']);

        $this->assertSame($scanner, $result);
    }

    public function testReturnAllFromIndex()
    {
        $scanner = new ParallelScanner($this->getConnection(), 'test_table', 'test_index');
        $result = $scanner->returnAllFromIndex();

        $this->assertSame($scanner, $result);
    }

    public function testMatchAll()
    {
        $scanner = new ParallelScanner($this->getConnection(), 'test_table', 'test_index');
        $result = $scanner->matchAll();

        $this->assertSame($scanner, $result);
    }

    public function testQuery()
    {
        $scanner = new ParallelScanner($this->getConnection(), 'test_table', 'test_index');
        $result = $scanner->query(['query_type' => 1]);

        $this->assertSame($scanner, $result);
    }

    public function testChainedMethods()
    {
        $scanner = $this->getConnection()
            ->parallelScanner('test_table', 'test_index')
            ->limit(100)
            ->aliveTime(30)
            ->columns(['id', 'name'])
            ->matchAll();

        $this->assertInstanceOf(ParallelScanner::class, $scanner);
    }
}
