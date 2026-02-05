<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/27
 * Time: 16:50.
 */

namespace HughCube\Laravel\OTS\Tests\Query;

use HughCube\Laravel\OTS\Query\Builder;
use HughCube\Laravel\OTS\Tests\TestCase;
use Illuminate\Database\Query\Builder as IlluminateBuilder;

class BuilderTest extends TestCase
{
    public function testInstanceOf()
    {
        $builder = new Builder($this->getConnection());
        $this->assertInstanceOf(IlluminateBuilder::class, $builder);
    }

    public function testBuilderFromConnection()
    {
        $builder = $this->getConnection()->table('cache');
        // The connection returns the default Laravel Query Builder
        // unless we override the query() method in Connection
        $this->assertInstanceOf(\Illuminate\Database\Query\Builder::class, $builder);
    }

    public function testBuilderWithTable()
    {
        $builder = new Builder($this->getConnection());
        $builder->from('test_table');

        $this->assertSame('test_table', $builder->from);
    }

    public function testBuilderQuery()
    {
        $builder = new Builder($this->getConnection());
        $builder->from('test_table');

        $this->assertInstanceOf(Builder::class, $builder);
    }
}
