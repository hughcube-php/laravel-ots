<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/27
 * Time: 18:43
 */

namespace HughCube\Laravel\OTS\Schema;

use Closure;
use HughCube\Laravel\OTS\Connection;

class Blueprint extends \Illuminate\Database\Schema\Blueprint
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param  Connection  $connection
     * @param  string  $table
     * @param  Closure|null  $callback
     * @param  string  $prefix
     */
    public function __construct(Connection $connection, string $table, Closure $callback = null, string $prefix = '')
    {
        $this->connection = $connection;

        parent::__construct($table, $callback, $prefix);
    }
}
