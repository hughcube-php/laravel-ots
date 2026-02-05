<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/8
 * Time: 11:02.
 */

namespace HughCube\Laravel\OTS\Tests\Cache;

use Closure;
use HughCube\Laravel\OTS\Cache\Store;
use HughCube\Laravel\OTS\Connection;
use HughCube\Laravel\OTS\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class AttributeTest extends TestCase
{
    protected function getStore(): Store
    {
        return Cache::store('ots')->getStore();
    }

    public function testGetPrefix()
    {
        $store = $this->getStore();
        $prefix = $store->getPrefix();

        $this->assertIsString($prefix);
    }

    public function testGetTable()
    {
        $store = $this->getStore();
        $table = $store->getTable();

        $this->assertSame('cache', $table);
    }

    public function testGetOts()
    {
        $store = $this->getStore();
        $ots = $store->getOts();

        $this->assertInstanceOf(Connection::class, $ots);
    }

    public function testMakePrimaryKey()
    {
        $store = $this->getStore();

        $makePrimaryKey = Closure::bind(function ($key) {
            return $this->makePrimaryKey($key);
        }, $store, Store::class);

        $primaryKey = $makePrimaryKey('test_key');

        $this->assertIsArray($primaryKey);
        $this->assertCount(3, $primaryKey);
        $this->assertSame(['key', 'test_key'], $primaryKey[0]);
        $this->assertSame('prefix', $primaryKey[1][0]);
        $this->assertSame(['type', 'cache'], $primaryKey[2]);
    }

    public function testMakeAttributeColumns()
    {
        $store = $this->getStore();

        $makeAttributeColumns = Closure::bind(function ($value, $seconds = null) {
            return $this->makeAttributeColumns($value, $seconds);
        }, $store, Store::class);

        // Test with value and seconds
        $columns = $makeAttributeColumns('test_value', 3600);
        $this->assertIsArray($columns);
        $this->assertGreaterThanOrEqual(2, count($columns));

        // Check created_at column exists
        $hasCreatedAt = false;
        $hasValue = false;
        $hasExpiration = false;
        foreach ($columns as $column) {
            if ($column[0] === 'created_at') {
                $hasCreatedAt = true;
            }
            if ($column[0] === 'value') {
                $hasValue = true;
            }
            if ($column[0] === 'expiration') {
                $hasExpiration = true;
            }
        }
        $this->assertTrue($hasCreatedAt);
        $this->assertTrue($hasValue);
        $this->assertTrue($hasExpiration);

        // Test without seconds (forever)
        $columnsForever = $makeAttributeColumns('test_value', null);
        $hasExpiration = false;
        foreach ($columnsForever as $column) {
            if ($column[0] === 'expiration') {
                $hasExpiration = true;
            }
        }
        $this->assertFalse($hasExpiration);
    }

    public function testSerializeAndUnserialize()
    {
        $store = $this->getStore();

        $serialize = Closure::bind(function ($value) {
            return $this->serialize($value);
        }, $store, Store::class);

        $unserialize = Closure::bind(function ($value) {
            return $this->unserialize($value);
        }, $store, Store::class);

        // Test numeric values
        $this->assertSame('123', $serialize(123));
        $this->assertSame('123.45', $serialize(123.45));
        $this->assertSame('123', $serialize('123'));

        // Test non-numeric values
        $serialized = $serialize(['test' => 'array']);
        $this->assertSame(['test' => 'array'], $unserialize($serialized));

        $serialized = $serialize('non-numeric string');
        $this->assertSame('non-numeric string', $unserialize($serialized));

        // Test numeric unserialize
        $this->assertEquals(123, $unserialize('123'));
        $this->assertEquals(123.45, $unserialize('123.45'));
    }

    public function testParseValueInOtsResponse()
    {
        $store = $this->getStore();

        $parseValue = Closure::bind(function ($response) {
            return $this->parseValueInOtsResponse($response);
        }, $store, Store::class);

        // Test response without value
        $response = ['primary_key' => [], 'attribute_columns' => []];
        $this->assertNull($parseValue($response));

        // Test response with expired value
        $response = [
            'primary_key' => [['key', 'test']],
            'attribute_columns' => [
                ['value', serialize('test_value')],
                ['expiration', time() - 1000],
            ],
        ];
        $this->assertNull($parseValue($response));

        // Test response with valid value
        $response = [
            'primary_key' => [['key', 'test']],
            'attribute_columns' => [
                ['value', serialize('test_value')],
                ['expiration', time() + 1000],
            ],
        ];
        $this->assertSame('test_value', $parseValue($response));
    }

    public function testParseKeyInOtsResponse()
    {
        $store = $this->getStore();

        $parseKey = Closure::bind(function ($response) {
            return $this->parseKeyInOtsResponse($response);
        }, $store, Store::class);

        // Test response without key
        $response = ['primary_key' => [], 'attribute_columns' => []];
        $this->assertNull($parseKey($response));

        // Test response with key
        $response = [
            'primary_key' => [['key', 'test_key']],
            'attribute_columns' => [],
        ];
        $this->assertSame('test_key', $parseKey($response));
    }

    public function testSerializeSpecialValues()
    {
        $store = $this->getStore();

        $serialize = Closure::bind(function ($value) {
            return $this->serialize($value);
        }, $store, Store::class);

        // Test INF
        $serializedInf = $serialize(INF);
        $this->assertNotEquals('INF', $serializedInf);

        // Test -INF
        $serializedNegInf = $serialize(-INF);
        $this->assertNotEquals('-INF', $serializedNegInf);

        // Test NAN
        $serializedNan = $serialize(NAN);
        $this->assertNotEquals('NAN', $serializedNan);
    }
}
