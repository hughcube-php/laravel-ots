<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/3
 * Time: 17:08.
 */

namespace HughCube\Laravel\OTS\Tests;

use Aliyun\OTS\OTSClient;
use DateTimeInterface;
use Exception;
use HughCube\Laravel\OTS\OTS\Handlers\RequestContext;
use HughCube\Laravel\OTS\Query\Grammar as QueryGrammar;
use HughCube\Laravel\OTS\Query\Processor as QueryProcessor;
use HughCube\Laravel\OTS\Schema\Builder as SchemaBuilder;
use HughCube\Laravel\OTS\Schema\Grammar as SchemaGrammar;
use Illuminate\Database\Connection as IlluminateConnection;
use Illuminate\Support\Carbon;

class ConnectionTest extends TestCase
{
    public function testInstanceOf()
    {
        $this->assertInstanceOf(IlluminateConnection::class, $this->getConnection());
    }

    public function testGetOts()
    {
        $this->skipIfNetworkUnavailable();
        $ots = $this->getConnection()->getOts();
        $this->assertInstanceOf(OTSClient::class, $ots);

        // Test caching - should return the same instance
        $this->assertSame($ots, $this->getConnection()->getOts());
    }

    public function testGetDatabaseName()
    {
        $this->skipIfNetworkUnavailable();
        $databaseName = $this->getConnection()->getDatabaseName();
        $this->assertIsString($databaseName);
        $this->assertSame(env('OTS_INSTANCE_NAME'), $databaseName);
    }

    public function testDisconnect()
    {
        $this->skipIfNetworkUnavailable();
        $connection = $this->getConnection();
        $ots1 = $connection->getOts();

        $connection->disconnect();

        $ots2 = $connection->getOts();
        $this->assertNotSame($ots1, $ots2);
    }

    public function testGetDriverName()
    {
        $this->assertSame('ots', $this->getConnection()->getDriverName());
    }

    public function testGetSchemaBuilder()
    {
        $builder = $this->getConnection()->getSchemaBuilder();
        $this->assertInstanceOf(SchemaBuilder::class, $builder);
    }

    public function testDefaultSchemaGrammar()
    {
        $connection = $this->getConnection();
        $grammar = $connection->getSchemaGrammar();
        $this->assertInstanceOf(SchemaGrammar::class, $grammar);
    }

    public function testDefaultQueryGrammar()
    {
        $connection = $this->getConnection();
        $grammar = $connection->getQueryGrammar();
        $this->assertInstanceOf(QueryGrammar::class, $grammar);
    }

    public function testDefaultPostProcessor()
    {
        $connection = $this->getConnection();
        $processor = $connection->getPostProcessor();
        $this->assertInstanceOf(QueryProcessor::class, $processor);
    }

    /**
     * @throws Exception
     */
    public function testPutRowAndReturnIdWithoutAutoIncrement()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The self-increasing primary key is not declared!');

        $this->getConnection()->putRowAndReturnId([
            'table_name' => 'test',
            'primary_key' => [
                ['key' => 'test_key'],
            ],
            'attribute_columns' => [],
        ]);
    }

    public function testMustBatchWriteRowSuccess()
    {
        $this->skipIfNetworkUnavailable();
        $this->skipIfCacheTableNotExists();

        $key = 'test_batch_' . md5(random_bytes(16));

        $request = [
            'tables' => [
                [
                    'table_name' => 'cache',
                    'rows' => [
                        [
                            'operation_type' => 'PUT',
                            'condition' => 'IGNORE',
                            'primary_key' => [
                                ['key', $key],
                                ['prefix', 'test'],
                                ['type', 'cache'],
                            ],
                            'attribute_columns' => [
                                ['value', 'test_value'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->getConnection()->mustBatchWriteRow($request);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('tables', $response);

        // Clean up
        $this->getConnection()->deleteRow([
            'table_name' => 'cache',
            'condition' => 'IGNORE',
            'primary_key' => [
                ['key', $key],
                ['prefix', 'test'],
                ['type', 'cache'],
            ],
        ]);
    }

    public function testParseAutoIncId()
    {
        $row = [
            'primary_key' => [
                ['id', 12345],
                ['other', 'value'],
            ],
        ];

        $id = $this->getConnection()->parseAutoIncId($row, 'id');
        $this->assertSame(12345, $id);

        $id = $this->getConnection()->parseAutoIncId($row, 'nonexistent');
        $this->assertNull($id);
    }

    public function testParseRowColumns()
    {
        $row = [
            'primary_key' => [
                ['pk1', 'value1'],
                ['pk2', 123],
            ],
            'attribute_columns' => [
                ['attr1', 'attr_value1'],
                ['attr2', 456],
            ],
        ];

        $columns = $this->getConnection()->parseRowColumns($row);
        $this->assertIsArray($columns);
        $this->assertSame('value1', $columns['pk1']);
        $this->assertSame(123, $columns['pk2']);
        $this->assertSame('attr_value1', $columns['attr1']);
        $this->assertSame(456, $columns['attr2']);
    }

    /**
     * @throws Exception
     */
    public function testMustParseRowAutoId()
    {
        $row = [
            'primary_key' => [
                ['id', 12345],
            ],
        ];

        $id = $this->getConnection()->mustParseRowAutoId($row, 'id');
        $this->assertSame(12345, $id);
    }

    /**
     * @throws Exception
     */
    public function testMustParseRowAutoIdThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to obtain id.');

        $row = [
            'primary_key' => [
                ['other', 'not_an_id'],
            ],
        ];

        $this->getConnection()->mustParseRowAutoId($row, 'id');
    }

    public function testIsSuccessBatchWriteResponse()
    {
        $successResponse = [
            'tables' => [
                [
                    'table_name' => 'test',
                    'rows' => [
                        ['is_ok' => true],
                        ['is_ok' => true],
                    ],
                ],
            ],
        ];

        $this->assertTrue($this->getConnection()->isSuccessBatchWriteResponse($successResponse));

        $failResponse = [
            'tables' => [
                [
                    'table_name' => 'test',
                    'rows' => [
                        ['is_ok' => true],
                        ['is_ok' => false],
                    ],
                ],
            ],
        ];

        $this->assertFalse($this->getConnection()->isSuccessBatchWriteResponse($failResponse));

        $emptyResponse = [];
        $this->assertFalse($this->getConnection()->isSuccessBatchWriteResponse($emptyResponse));
    }

    /**
     * @throws Exception
     */
    public function testAssertSuccessBatchWriteResponseSuccess()
    {
        $successResponse = [
            'tables' => [
                [
                    'table_name' => 'test',
                    'rows' => [
                        ['is_ok' => true],
                    ],
                ],
            ],
        ];

        // Should not throw exception
        $this->getConnection()->assertSuccessBatchWriteResponse($successResponse);
        $this->assertTrue(true);
    }

    /**
     * @throws Exception
     */
    public function testAssertSuccessBatchWriteResponseFailure()
    {
        $this->expectException(Exception::class);

        $failResponse = [
            'tables' => [
                [
                    'table_name' => 'test',
                    'rows' => [
                        ['is_ok' => false],
                    ],
                ],
            ],
        ];

        $this->getConnection()->assertSuccessBatchWriteResponse($failResponse);
    }

    /**
     * @throws Exception
     */
    public function testAssertSuccessBatchWriteResponseEmpty()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Abnormal operation.');

        $this->getConnection()->assertSuccessBatchWriteResponse([]);
    }

    public function testAvailableDate()
    {
        $date = $this->getConnection()->availableDate(0);
        $this->assertIsString($date);

        $parsed = Carbon::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $date);
        $this->assertInstanceOf(Carbon::class, $parsed);

        $dateWithDelay = $this->getConnection()->availableDate(60);
        $parsedWithDelay = Carbon::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $dateWithDelay);
        $this->assertInstanceOf(Carbon::class, $parsedWithDelay);
        $this->assertTrue($parsedWithDelay->gt($parsed));
    }

    public function testAvailableDateToDateTime()
    {
        $date = $this->getConnection()->availableDate(0);
        $dateTime = $this->getConnection()->availableDateToDateTime($date);

        $this->assertInstanceOf(Carbon::class, $dateTime);

        $nullResult = $this->getConnection()->availableDateToDateTime(null);
        $this->assertNull($nullResult);
    }

    public function testCallProxiesToOtsClient()
    {
        $this->skipIfNetworkUnavailable();

        // Test that __call proxies to OTSClient methods
        $tables = $this->getConnection()->listTable([]);
        $this->assertIsArray($tables);
    }

    public function testAsyncDoHandle()
    {
        $this->skipIfNetworkUnavailable();

        $context = $this->getConnection()->asyncDoHandle('ListTable', []);
        $this->assertTrue(method_exists($context, 'wait'));

        $response = $context->wait();
        $this->assertIsArray($response);
    }

    public function testAsyncSearch()
    {
        $this->skipIfNetworkUnavailable();
        $this->skipIfCacheTableNotExists();
        $this->skipIfCacheIndexNotExists();

        $context = $this->getConnection()->asyncSearch([
            'table_name' => 'cache',
            'index_name' => 'cache_index',
            'search_query' => [
                'offset' => 0,
                'limit' => 1,
                'get_total_count' => true,
                'query' => [
                    'query_type' => \Aliyun\OTS\Consts\QueryTypeConst::MATCH_ALL_QUERY,
                ],
            ],
            'columns_to_get' => [
                'return_type' => \Aliyun\OTS\Consts\ColumnReturnTypeConst::RETURN_ALL,
            ],
        ]);

        $this->assertTrue(method_exists($context, 'wait'));

        $response = $context->wait();
        $this->assertIsArray($response);
        $this->assertArrayHasKey('total_hits', $response);
    }

    public function testAsyncSqlQuery()
    {
        $this->skipIfNetworkUnavailable();
        $this->skipIfCacheTableNotExists();
        $this->skipIfSqlNotAvailable();

        $context = $this->getConnection()->asyncSqlQuery([
            'query' => 'SELECT * FROM cache LIMIT 1',
        ]);

        $this->assertTrue(method_exists($context, 'wait'));

        $response = $context->wait();
        $this->assertIsArray($response);
    }
}
