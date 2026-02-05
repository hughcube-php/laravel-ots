<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/27
 * Time: 16:50.
 */

namespace HughCube\Laravel\OTS\Tests\Query;

use HughCube\Laravel\OTS\Query\Grammar;
use HughCube\Laravel\OTS\Tests\TestCase;
use Illuminate\Database\Query\Grammars\Grammar as IlluminateGrammar;

class GrammarTest extends TestCase
{
    public function testInstanceOf()
    {
        // Get Grammar through the connection
        $grammar = $this->getConnection()->getQueryGrammar();
        $this->assertInstanceOf(IlluminateGrammar::class, $grammar);
    }

    public function testGrammarFromConnection()
    {
        $grammar = $this->getConnection()->getQueryGrammar();
        $this->assertInstanceOf(Grammar::class, $grammar);
    }
}
