<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/27
 * Time: 16:49.
 */

namespace HughCube\Laravel\OTS\Tests\Query;

use HughCube\Laravel\OTS\Query\Processor;
use HughCube\Laravel\OTS\Tests\TestCase;
use Illuminate\Database\Query\Processors\Processor as IlluminateProcessor;

class ProcessorTest extends TestCase
{
    public function testInstanceOf()
    {
        $processor = new Processor();
        $this->assertInstanceOf(IlluminateProcessor::class, $processor);
    }

    public function testProcessorFromConnection()
    {
        $processor = $this->getConnection()->getPostProcessor();
        $this->assertInstanceOf(Processor::class, $processor);
    }
}
