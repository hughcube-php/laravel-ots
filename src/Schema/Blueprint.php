<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/27
 * Time: 18:43.
 */

namespace HughCube\Laravel\OTS\Schema;

use Aliyun\OTS\Consts\DefinedColumnTypeConst;
use Closure;
use HughCube\Laravel\OTS\Connection;
use Illuminate\Database\Connection as IlluminateConnection;

/**
 * @method Connection getConnection()
 */
class Blueprint extends \Illuminate\Database\Schema\Blueprint
{
    /**
     * Defined columns for the table.
     *
     * @var array
     */
    protected $definedColumns = [];

    /**
     * @param Connection|IlluminateConnection $connection
     * @param string                          $table
     * @param Closure|null                    $callback
     */
    public function __construct(IlluminateConnection $connection, $table, ?Closure $callback = null)
    {
        parent::__construct($connection, $table, $callback);
    }

    /**
     * Add a defined column (pre-defined attribute column).
     *
     * @param string $name Column name
     * @param string $type Column type (DCT_INTEGER, DCT_DOUBLE, DCT_BOOLEAN, DCT_STRING, DCT_BLOB)
     *
     * @return $this
     */
    public function definedColumn(string $name, string $type = DefinedColumnTypeConst::DCT_STRING)
    {
        $this->definedColumns[] = [$name, $type];

        return $this;
    }

    /**
     * Add a defined integer column.
     *
     * @return $this
     */
    public function definedInteger(string $name)
    {
        return $this->definedColumn($name, DefinedColumnTypeConst::DCT_INTEGER);
    }

    /**
     * Add a defined double column.
     *
     * @return $this
     */
    public function definedDouble(string $name)
    {
        return $this->definedColumn($name, DefinedColumnTypeConst::DCT_DOUBLE);
    }

    /**
     * Add a defined boolean column.
     *
     * @return $this
     */
    public function definedBoolean(string $name)
    {
        return $this->definedColumn($name, DefinedColumnTypeConst::DCT_BOOLEAN);
    }

    /**
     * Add a defined string column.
     *
     * @return $this
     */
    public function definedString(string $name)
    {
        return $this->definedColumn($name, DefinedColumnTypeConst::DCT_STRING);
    }

    /**
     * Add a defined blob column.
     *
     * @return $this
     */
    public function definedBlob(string $name)
    {
        return $this->definedColumn($name, DefinedColumnTypeConst::DCT_BLOB);
    }

    /**
     * Get the defined columns.
     *
     * @return array
     */
    public function getDefinedColumns(): array
    {
        return $this->definedColumns;
    }
}
