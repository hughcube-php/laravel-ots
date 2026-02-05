<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/27
 * Time: 18:05.
 */

namespace HughCube\Laravel\OTS\Tests\Schema;

use Aliyun\OTS\OTSClientException;
use Aliyun\OTS\OTSServerException;
use HughCube\Laravel\OTS\Exceptions\DropTableException;
use HughCube\Laravel\OTS\Schema\Blueprint;
use HughCube\Laravel\OTS\Schema\Builder;
use HughCube\Laravel\OTS\Tests\TestCase;
use Illuminate\Support\Str;
use LogicException;

class BuilderTest extends TestCase
{
    /**
     * @throws OTSServerException
     * @throws OTSClientException
     */
    public function testInstanceOf()
    {
        $builder = $this->getConnection()->getSchemaBuilder();
        $this->assertInstanceOf(Builder::class, $builder);
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     */
    public function testHasTable()
    {
        $table = 'a'.Str::random();

        $this->getConnection()->getSchemaBuilder()->dropIfExists($table);
        $this->assertFalse($this->getConnection()->getSchemaBuilder()->hasTable($table));

        $this->getConnection()->getSchemaBuilder()->create($table, function (Blueprint $table) {
            $table->char('pk1')->primary();
            $table->binary('pk2')->primary();
            $table->bigInteger('pk3')->primary();
            $table->bigIncrements('pk4')->primary();
        });
        $this->assertTrue($this->getConnection()->getSchemaBuilder()->hasTable($table));

        // Clean up
        $this->getConnection()->getSchemaBuilder()->dropIfExists($table);
    }

    public function testHasTableReturnsFalseForNonExistent()
    {
        $table = 'nonexistent_table_'.Str::random();
        $this->assertFalse($this->getConnection()->getSchemaBuilder()->hasTable($table));
    }

    public function testHasColumn()
    {
        // OTS always returns true for hasColumn
        $this->assertTrue($this->getConnection()->getSchemaBuilder()->hasColumn('any_table', 'any_column'));
    }

    public function testHasColumns()
    {
        // OTS always returns true for hasColumns
        $this->assertTrue($this->getConnection()->getSchemaBuilder()->hasColumns('any_table', ['col1', 'col2']));
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     */
    public function testCreate()
    {
        $table = 'a'.Str::random();

        $this->getConnection()->getSchemaBuilder()->dropIfExists($table);
        $this->assertFalse($this->getConnection()->getSchemaBuilder()->hasTable($table));

        $this->getConnection()->getSchemaBuilder()->create($table, function (Blueprint $table) {
            $table->char('pk1')->primary();
            $table->binary('pk2')->primary();
            $table->bigInteger('pk3')->primary();
            $table->bigIncrements('pk4')->primary();
        });
        $this->assertTrue($this->getConnection()->getSchemaBuilder()->hasTable($table));

        // Clean up
        $this->getConnection()->getSchemaBuilder()->dropIfExists($table);
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     */
    public function testCreateWithNonPrimaryKey()
    {
        $this->expectException(OTSClientException::class);
        $this->expectExceptionMessage('Only primary keys can be defined!');

        $table = 'a'.Str::random();

        $this->getConnection()->getSchemaBuilder()->dropIfExists($table);

        $this->getConnection()->getSchemaBuilder()->create($table, function (Blueprint $table) {
            $table->char('pk1'); // Not primary
        });
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     */
    public function testCreateWithCustomThroughput()
    {
        $table = 'a'.Str::random();

        $this->getConnection()->getSchemaBuilder()->dropIfExists($table);

        $this->getConnection()->getSchemaBuilder()->create(
            $table,
            function (Blueprint $table) {
                $table->char('pk1')->primary();
            },
            ['capacity_unit' => ['read' => 0, 'write' => 0]],
            ['time_to_live' => -1, 'max_versions' => 1, 'deviation_cell_version_in_sec' => 86400]
        );

        $this->assertTrue($this->getConnection()->getSchemaBuilder()->hasTable($table));

        // Clean up
        $this->getConnection()->getSchemaBuilder()->dropIfExists($table);
    }

    /**
     * @throws OTSClientException
     * @throws OTSServerException
     * @throws DropTableException
     *
     * @return void
     */
    public function testDrop()
    {
        $table = 'a'.Str::random();

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

    public function testDropColumns()
    {
        // OTS does not support dropping columns, method is empty
        $this->getConnection()->getSchemaBuilder()->dropColumns('any_table', ['col1', 'col2']);
        $this->assertTrue(true);
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     */
    public function testGetAllTables()
    {
        $tables = $this->getConnection()->getSchemaBuilder()->getAllTables();
        $this->assertIsArray($tables);
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     */
    public function testDropAllTables()
    {
        $table1 = 'a'.Str::random();
        $table2 = 'a'.Str::random();

        // Clean up first
        $this->getConnection()->getSchemaBuilder()->dropIfExists($table1);
        $this->getConnection()->getSchemaBuilder()->dropIfExists($table2);

        // Create two tables
        $this->getConnection()->getSchemaBuilder()->create($table1, function (Blueprint $table) {
            $table->char('pk1')->primary();
        });
        $this->getConnection()->getSchemaBuilder()->create($table2, function (Blueprint $table) {
            $table->char('pk1')->primary();
        });

        $this->assertTrue($this->getConnection()->getSchemaBuilder()->hasTable($table1));
        $this->assertTrue($this->getConnection()->getSchemaBuilder()->hasTable($table2));

        // Drop all tables except table2
        $this->getConnection()->getSchemaBuilder()->dropAllTables([$table2]);

        $remainingTables = $this->getConnection()->getSchemaBuilder()->getAllTables();
        $this->assertContains($table2, $remainingTables);
        $this->assertNotContains($table1, $remainingTables);

        // Clean up
        $this->getConnection()->getSchemaBuilder()->dropIfExists($table2);
    }

    public function testRename()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This database driver does not support rename table.');

        $this->getConnection()->getSchemaBuilder()->rename('old_table', 'new_table');
    }

    public function testDropIfExistsNonExistent()
    {
        $table = 'nonexistent_table_'.Str::random();

        // Should not throw exception
        $this->getConnection()->getSchemaBuilder()->dropIfExists($table);
        $this->assertTrue(true);
    }
}
