<?php

namespace HughCube\Laravel\OTS\Tests\Ots;

use Aliyun\OTS\OTSClientException;
use HughCube\Laravel\OTS\OTS\DefinedColumnApi;
use HughCube\Laravel\OTS\Schema\Blueprint;
use HughCube\Laravel\OTS\Tests\TestCase;
use Illuminate\Support\Str;

class DefinedColumnApiTest extends TestCase
{
    // =================================================================
    // Wire format 编码单元测试（无网络依赖）
    // =================================================================

    public function testEncodeAddSingleColumn(): void
    {
        // "t1" + [{name:"a", type:INTEGER(=1)}]
        // 0a 02 74 31              table_name="t1"
        // 12 05                    columns (embedded, len=5)
        //   0a 01 61               name="a"
        //   10 01                  type=INTEGER(1)
        $expected = "\x0a\x02t1\x12\x05\x0a\x01a\x10\x01";
        $actual = DefinedColumnApi::encodeAddDefinedColumnRequest('t1', [
            ['name' => 'a', 'type' => 'INTEGER'],
        ]);
        $this->assertSame($expected, $actual);
    }

    public function testEncodeAddMultipleColumnsAllTypes(): void
    {
        // DefinedColumnType: INTEGER=1, DOUBLE=2, BOOLEAN=3, STRING=4, BLOB=7
        $body = DefinedColumnApi::encodeAddDefinedColumnRequest('x', [
            ['name' => 'i', 'type' => 'INTEGER'],
            ['name' => 'd', 'type' => 'DOUBLE'],
            ['name' => 'b', 'type' => 'BOOLEAN'],
            ['name' => 's', 'type' => 'STRING'],
            ['name' => 'o', 'type' => 'BLOB'],
            ['name' => 'n', 'type' => 'BINARY'], // BINARY 作为 BLOB 别名
        ]);

        $expected = "\x0a\x01x"                        // table="x"
            . "\x12\x05\x0a\x01i\x10\x01"              // INTEGER
            . "\x12\x05\x0a\x01d\x10\x02"              // DOUBLE
            . "\x12\x05\x0a\x01b\x10\x03"              // BOOLEAN
            . "\x12\x05\x0a\x01s\x10\x04"              // STRING
            . "\x12\x05\x0a\x01o\x10\x07"              // BLOB
            . "\x12\x05\x0a\x01n\x10\x07";             // BINARY → 7

        $this->assertSame($expected, $body);
    }

    public function testEncodeAddLongStringUsesMultiByteVarint(): void
    {
        // 构造 name 长 200 字节，强制 length varint = 2 bytes (C8 01)
        $name = str_repeat('x', 200);
        $body = DefinedColumnApi::encodeAddDefinedColumnRequest('t', [
            ['name' => $name, 'type' => 'STRING'],
        ]);

        // 内部 schema: 0a C8 01 <200x> 10 04  => 1+2+200+2 = 205 bytes
        // 外层 columns: 12 CD 01 <205 bytes>  (varint(205) = CD 01)
        // table part:   0a 01 t               (3 bytes)
        $innerSchema = "\x0a\xC8\x01" . $name . "\x10\x04";
        $this->assertSame(205, strlen($innerSchema));
        $expected = "\x0a\x01t" . "\x12\xCD\x01" . $innerSchema;
        $this->assertSame($expected, $body);
    }

    public function testEncodeAddRejectsEmptyName(): void
    {
        $this->expectException(OTSClientException::class);
        DefinedColumnApi::encodeAddDefinedColumnRequest('t', [['name' => '', 'type' => 'STRING']]);
    }

    public function testEncodeAddRejectsUnknownType(): void
    {
        $this->expectException(OTSClientException::class);
        DefinedColumnApi::encodeAddDefinedColumnRequest('t', [['name' => 'a', 'type' => 'UNKNOWN']]);
    }

    public function testEncodeAddAcceptsLowercaseType(): void
    {
        // 实现里对类型字符串做了 strtoupper，'string' 应能被接受
        $body = DefinedColumnApi::encodeAddDefinedColumnRequest('t', [
            ['name' => 'a', 'type' => 'string'],
        ]);
        $expected = "\x0a\x01t\x12\x05\x0a\x01a\x10\x04";
        $this->assertSame($expected, $body);
    }

    public function testEncodeDeleteMultipleColumns(): void
    {
        // "t1" + ["a","b"]
        // 0a 02 74 31   table_name="t1"
        // 12 01 61      columns="a"
        // 12 01 62      columns="b"
        $expected = "\x0a\x02t1\x12\x01a\x12\x01b";
        $actual = DefinedColumnApi::encodeDeleteDefinedColumnRequest('t1', ['a', 'b']);
        $this->assertSame($expected, $actual);
    }

    public function testEncodeDeleteTableNameOnly(): void
    {
        // 只有 table_name，没有 columns
        $expected = "\x0a\x02t1";
        $actual = DefinedColumnApi::encodeDeleteDefinedColumnRequest('t1', []);
        $this->assertSame($expected, $actual);
    }

    // =================================================================
    // Connection 入口的参数校验（无网络依赖）
    // =================================================================

    public function testAddDefinedColumnRequiresTableName(): void
    {
        $this->expectException(OTSClientException::class);
        $this->expectExceptionMessage('table_name is required');
        $this->getConnection()->addDefinedColumn([
            'columns' => [['name' => 'a', 'type' => 'STRING']],
        ]);
    }

    public function testDeleteDefinedColumnRequiresTableName(): void
    {
        $this->expectException(OTSClientException::class);
        $this->expectExceptionMessage('table_name is required');
        $this->getConnection()->deleteDefinedColumn([
            'columns' => ['a'],
        ]);
    }

    public function testAddDefinedColumnIsNoOpWhenEmpty(): void
    {
        // 空 columns 不应发起请求，不会抛异常
        $this->getConnection()->addDefinedColumn([
            'table_name' => 'any_table',
            'columns'    => [],
        ]);
        $this->assertTrue(true);
    }

    public function testDeleteDefinedColumnIsNoOpWhenEmpty(): void
    {
        $this->getConnection()->deleteDefinedColumn([
            'table_name' => 'any_table',
            'columns'    => [],
        ]);
        $this->assertTrue(true);
    }

    // =================================================================
    // 集成测试：真实调用 OTS（需要网络）
    // =================================================================

    public function testAddAndDeleteDefinedColumnAgainstRealTable(): void
    {
        $this->skipIfNetworkUnavailable();

        $table = 'a' . Str::random();
        $builder = $this->getConnection()->getSchemaBuilder();

        // 建一个只有主键、无预定义列的表
        $builder->dropIfExists($table);
        $builder->create($table, function (Blueprint $blueprint) {
            $blueprint->char('id')->primary();
        });

        try {
            // 确认初始没有预定义列
            $desc = $this->getConnection()->describeTable(['table_name' => $table]);
            $initial = $desc['table_meta']['defined_column'] ?? [];
            $this->assertEmpty($initial, '新建表不应有预定义列');

            // AddDefinedColumn
            $this->getConnection()->addDefinedColumn([
                'table_name' => $table,
                'columns'    => [
                    ['name' => 'score', 'type' => 'DOUBLE'],
                    ['name' => 'title', 'type' => 'STRING'],
                ],
            ]);

            $desc = $this->getConnection()->describeTable(['table_name' => $table]);
            $names = array_map(fn($c) => $c[0] ?? $c['name'] ?? null, $desc['table_meta']['defined_column'] ?? []);
            $this->assertContains('score', $names);
            $this->assertContains('title', $names);

            // DeleteDefinedColumn
            $this->getConnection()->deleteDefinedColumn([
                'table_name' => $table,
                'columns'    => ['score'],
            ]);

            $desc = $this->getConnection()->describeTable(['table_name' => $table]);
            $names = array_map(fn($c) => $c[0] ?? $c['name'] ?? null, $desc['table_meta']['defined_column'] ?? []);
            $this->assertNotContains('score', $names);
            $this->assertContains('title', $names, 'title 不应受影响');
        } finally {
            $builder->dropIfExists($table);
        }
    }
}
