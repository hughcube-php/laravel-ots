<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/27
 * Time: 18:05.
 */

namespace HughCube\Laravel\OTS\Tests\Schema;

use Aliyun\OTS\Consts\PrimaryKeyTypeConst;
use HughCube\Laravel\OTS\Schema\Grammar;
use HughCube\Laravel\OTS\Tests\TestCase;
use Illuminate\Support\Fluent;
use RuntimeException;

class GrammarTest extends TestCase
{
    protected function getGrammar(): Grammar
    {
        return $this->getConnection()->getSchemaGrammar();
    }

    protected function makeColumn(string $type): Fluent
    {
        return new Fluent(['type' => $type]);
    }

    public function testTypeChar()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('char'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeString()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('string'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeTinyText()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('tinyText'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeText()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('text'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeMediumText()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('mediumText'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeLongText()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('longText'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeBigInteger()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('bigInteger'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_INTEGER, $type);
    }

    public function testTypeInteger()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('integer'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_INTEGER, $type);
    }

    public function testTypeMediumInteger()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('mediumInteger'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_INTEGER, $type);
    }

    public function testTypeTinyInteger()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('tinyInteger'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_INTEGER, $type);
    }

    public function testTypeSmallInteger()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('smallInteger'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_INTEGER, $type);
    }

    public function testTypeFloat()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported types.');

        $grammar = $this->getGrammar();
        $grammar->getType($this->makeColumn('float'));
    }

    public function testTypeDouble()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported types.');

        $grammar = $this->getGrammar();
        $grammar->getType($this->makeColumn('double'));
    }

    public function testTypeDecimal()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('decimal'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeBoolean()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('boolean'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_INTEGER, $type);
    }

    public function testTypeEnum()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported types.');

        $grammar = $this->getGrammar();
        $grammar->getType($this->makeColumn('enum'));
    }

    public function testTypeSet()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported types.');

        $grammar = $this->getGrammar();
        $grammar->getType($this->makeColumn('set'));
    }

    public function testTypeJson()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('json'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeJsonb()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('jsonb'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeDate()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('date'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeDateTime()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('dateTime'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeDateTimeTz()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('dateTimeTz'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeTime()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('time'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeTimeTz()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('timeTz'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeTimestamp()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('timestamp'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeTimestampTz()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('timestampTz'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeYear()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('year'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_INTEGER, $type);
    }

    public function testTypeBinary()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('binary'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_BINARY, $type);
    }

    public function testTypeUuid()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('uuid'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeIpAddress()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('ipAddress'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeMacAddress()
    {
        $grammar = $this->getGrammar();
        $type = $grammar->getType($this->makeColumn('macAddress'));
        $this->assertSame(PrimaryKeyTypeConst::CONST_STRING, $type);
    }

    public function testTypeGeometry()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported types.');

        $grammar = $this->getGrammar();
        $grammar->typeGeometry($this->makeColumn('geometry'));
    }

    public function testTypePoint()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported types.');

        $grammar = $this->getGrammar();
        $grammar->typePoint($this->makeColumn('point'));
    }

    public function testTypeLineString()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported types.');

        $grammar = $this->getGrammar();
        $grammar->typeLineString($this->makeColumn('lineString'));
    }

    public function testTypePolygon()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported types.');

        $grammar = $this->getGrammar();
        $grammar->typePolygon($this->makeColumn('polygon'));
    }

    public function testTypeGeometryCollection()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported types.');

        $grammar = $this->getGrammar();
        $grammar->typeGeometryCollection($this->makeColumn('geometryCollection'));
    }

    public function testTypeMultiPoint()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported types.');

        $grammar = $this->getGrammar();
        $grammar->typeMultiPoint($this->makeColumn('multiPoint'));
    }

    public function testTypeMultiLineString()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported types.');

        $grammar = $this->getGrammar();
        $grammar->typeMultiLineString($this->makeColumn('multiLineString'));
    }

    public function testTypeMultiPolygon()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported types.');

        $grammar = $this->getGrammar();
        $grammar->typeMultiPolygon($this->makeColumn('multiPolygon'));
    }
}
