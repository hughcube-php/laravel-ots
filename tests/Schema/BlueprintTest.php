<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/27
 * Time: 18:05.
 */

namespace HughCube\Laravel\OTS\Tests\Schema;

use HughCube\Laravel\OTS\Schema\Blueprint;
use HughCube\Laravel\OTS\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint as IlluminateBlueprint;

class BlueprintTest extends TestCase
{
    public function testInstanceOf()
    {
        $blueprint = new Blueprint($this->getConnection(), 'test_table');
        $this->assertInstanceOf(IlluminateBlueprint::class, $blueprint);
    }

    public function testConstructorWithCallback()
    {
        $callbackExecuted = false;
        $blueprint = new Blueprint($this->getConnection(), 'test_table', function ($table) use (&$callbackExecuted) {
            $callbackExecuted = true;
            $table->string('name');
        });

        $this->assertTrue($callbackExecuted);
    }

    public function testGetTable()
    {
        $blueprint = new Blueprint($this->getConnection(), 'test_table');
        $this->assertSame('test_table', $blueprint->getTable());
    }

    public function testAddColumns()
    {
        $blueprint = new Blueprint($this->getConnection(), 'test_table');
        $blueprint->string('pk1')->primary();
        $blueprint->bigInteger('pk2')->primary();
        $blueprint->binary('pk3')->primary();

        $columns = $blueprint->getColumns();
        $this->assertCount(3, $columns);
    }

    public function testAddAutoIncrement()
    {
        $blueprint = new Blueprint($this->getConnection(), 'test_table');
        $blueprint->bigIncrements('id')->primary();

        $columns = $blueprint->getColumns();
        $this->assertCount(1, $columns);
        $this->assertTrue($columns[0]->get('autoIncrement'));
    }
}
