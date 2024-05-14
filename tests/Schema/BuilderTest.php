<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/27
 * Time: 18:05
 */

namespace HughCube\Laravel\OTS\Tests\Schema;

use Aliyun\OTS\OTSClientException;
use Aliyun\OTS\OTSServerException;
use HughCube\Laravel\OTS\Exceptions\DropTableException;
use HughCube\Laravel\OTS\Schema\Blueprint;
use HughCube\Laravel\OTS\Tests\TestCase;
use Illuminate\Support\Str;

class BuilderTest extends TestCase
{
    /**
     * @throws OTSServerException
     * @throws OTSClientException
     */
    public function testHasTable()
    {
        $table = 'a' . Str::random();

        $this->getConnection()->getSchemaBuilder()->dropIfExists($table);
        $this->assertFalse($this->getConnection()->getSchemaBuilder()->hasTable($table));

        $this->getConnection()->getSchemaBuilder()->create($table, function (Blueprint $table) {
            $table->char('pk1')->primary();
            $table->binary('pk2')->primary();
            $table->bigInteger('pk3')->primary();
            $table->bigIncrements('pk4')->primary();
        });
        $this->assertTrue($this->getConnection()->getSchemaBuilder()->hasTable($table));
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     */
    public function testCreate()
    {
        $table = 'a' . Str::random();

        $this->getConnection()->getSchemaBuilder()->dropIfExists($table);
        $this->assertFalse($this->getConnection()->getSchemaBuilder()->hasTable($table));

        $this->getConnection()->getSchemaBuilder()->create($table, function (Blueprint $table) {
            $table->char('pk1')->primary();
            $table->binary('pk2')->primary();
            $table->bigInteger('pk3')->primary();
            $table->bigIncrements('pk4')->primary();
        });
        $this->assertTrue($this->getConnection()->getSchemaBuilder()->hasTable($table));

        $exception = null;
        try {
            $this->getConnection()->getSchemaBuilder()->create($table, function (Blueprint $table) {
                $table->char('pk1')->primary();
                $table->binary('pk2')->primary();
                $table->bigInteger('pk3')->primary();
                $table->bigIncrements('pk4');
            });
        } catch (OTSClientException $exception) {
        }
        $this->assertInstanceOf(OTSClientException::class, $exception);
    }

    /**
     * @return void
     * @throws OTSClientException
     * @throws OTSServerException
     * @throws DropTableException
     */
    public function testDrop()
    {
        $table = 'a' . Str::random();

        $this->getConnection()->getSchemaBuilder()->dropIfExists($table);
        $this->assertFalse($this->getConnection()->getSchemaBuilder()->hasTable($table));

        $this->getConnection()->getSchemaBuilder()->create($table, function (Blueprint $table) {
            $table->char('pk1')->primary();
            $table->binary('pk2')->primary();
            $table->bigInteger('pk3')->primary();
            $table->bigIncrements('pk4')->primary();
        });
        $this->assertTrue($this->getConnection()->getSchemaBuilder()->hasTable($table));

        $this->getConnection()->getSchemaBuilder()->drop($table);
        $this->assertFalse($this->getConnection()->getSchemaBuilder()->hasTable($table));
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     */
    public function testGetAllTables()
    {
        $this->assertIsArray($this->getConnection()->getSchemaBuilder()->getAllTables());
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     */
    public function testDropAllTables()
    {
        $table = 'a' . Str::random();

        $this->getConnection()->getSchemaBuilder()->dropIfExists($table);
        $this->assertFalse($this->getConnection()->getSchemaBuilder()->hasTable($table));

        $this->getConnection()->getSchemaBuilder()->create($table, function (Blueprint $table) {
            $table->char('pk1')->primary();
            $table->binary('pk2')->primary();
            $table->bigInteger('pk3')->primary();
            $table->bigIncrements('pk4')->primary();
        });

        $this->getConnection()->getSchemaBuilder()->dropAllTables(['cache']);

        $this->assertEmpty(array_diff(
            $this->getConnection()->getSchemaBuilder()->getAllTables(),
            ['cache']
        ));

        $this->getConnection()->getSchemaBuilder()->create('cache', function (Blueprint $table) {
            $table->char('key')->primary();
            $table->char('prefix')->primary();
            $table->char('type')->primary();
        });
    }
}
