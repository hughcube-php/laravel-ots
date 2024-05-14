<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/3
 * Time: 17:08.
 */

namespace HughCube\Laravel\OTS\Tests;

use HughCube\Laravel\OTS\Connection;
use Illuminate\Database\Connection as IlluminateConnection;

class ConnectionTest extends TestCase
{
    public function testInstanceOf()
    {
        $this->assertInstanceOf(IlluminateConnection::class, $this->getConnection());
    }
}
