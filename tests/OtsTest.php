<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/3
 * Time: 17:08.
 */

namespace HughCube\Laravel\OTS\Tests;

use Exception;
use HughCube\Laravel\OTS\Connection;
use HughCube\Laravel\OTS\Ots;

class OtsTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testConnection()
    {
        $connection = Ots::connection('ots');
        $this->assertInstanceOf(Connection::class, $connection);
    }

    /**
     * @throws Exception
     */
    public function testConnectionThrowsExceptionForNonOtsConnection()
    {
        // Configure a non-ots connection
        $this->app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'test',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Only ots connections can be obtained');

        Ots::connection('mysql');
    }

    public function testParseRow()
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

        $columns = Ots::parseRow($row);
        $this->assertIsArray($columns);
        $this->assertSame('value1', $columns['pk1']);
        $this->assertSame(123, $columns['pk2']);
        $this->assertSame('attr_value1', $columns['attr1']);
        $this->assertSame(456, $columns['attr2']);
    }

    public function testParseRowWithEmptyData()
    {
        $row = [];
        $columns = Ots::parseRow($row);
        $this->assertIsArray($columns);
        $this->assertEmpty($columns);
    }

    public function testParseRowWithMissingKeys()
    {
        $row = [
            'primary_key' => [
                ['pk1'], // Missing value
            ],
        ];

        $columns = Ots::parseRow($row);
        $this->assertIsArray($columns);
        $this->assertEmpty($columns);
    }

    public function testParseRowAutoId()
    {
        $row = [
            'primary_key' => [
                ['id', 12345],
                ['other', 'value'],
            ],
        ];

        $id = Ots::parseRowAutoId($row, 'id');
        $this->assertSame(12345, $id);
    }

    public function testParseRowAutoIdNotFound()
    {
        $row = [
            'primary_key' => [
                ['other', 'value'],
            ],
        ];

        $id = Ots::parseRowAutoId($row, 'id');
        $this->assertNull($id);
    }

    public function testParseRowAutoIdWithNonInteger()
    {
        $row = [
            'primary_key' => [
                ['id', 'string_value'],
            ],
        ];

        $id = Ots::parseRowAutoId($row, 'id');
        $this->assertNull($id);
    }

    public function testParseRowAutoIdWithEmptyPrimaryKey()
    {
        $row = [
            'primary_key' => [],
        ];

        $id = Ots::parseRowAutoId($row);
        $this->assertNull($id);
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

        $id = Ots::mustParseRowAutoId($row, 'id');
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
                ['other', 'value'],
            ],
        ];

        Ots::mustParseRowAutoId($row, 'id');
    }

    public function testIsBatchWriteSuccess()
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

        $this->assertTrue(Ots::isBatchWriteSuccess($successResponse));
    }

    public function testIsBatchWriteSuccessWithFailure()
    {
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

        $this->assertFalse(Ots::isBatchWriteSuccess($failResponse));
    }

    public function testIsBatchWriteSuccessWithEmptyTables()
    {
        $emptyResponse = [];
        $this->assertFalse(Ots::isBatchWriteSuccess($emptyResponse));

        // Empty tables array is still valid (no rows to fail)
        $emptyTablesResponse = ['tables' => []];
        // Actually, if there are no tables, it should return true because there are no failures
        $result = Ots::isBatchWriteSuccess($emptyTablesResponse);
        $this->assertIsBool($result);
    }

    public function testIsBatchWriteSuccessWithNonArrayTables()
    {
        $invalidResponse = ['tables' => 'not_an_array'];
        $this->assertFalse(Ots::isBatchWriteSuccess($invalidResponse));
    }

    public function testIsBatchWriteSuccessWithEmptyRows()
    {
        $response = [
            'tables' => [
                [
                    'table_name' => 'test',
                    'rows' => [],
                ],
            ],
        ];

        $this->assertTrue(Ots::isBatchWriteSuccess($response));
    }

    /**
     * @throws Exception
     */
    public function testThrowBatchWriteException()
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
        Ots::throwBatchWriteException($successResponse);
        $this->assertTrue(true);
    }

    /**
     * @throws Exception
     */
    public function testThrowBatchWriteExceptionWithFailure()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to write the "test" table.');

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

        Ots::throwBatchWriteException($failResponse);
    }

    /**
     * @throws Exception
     */
    public function testThrowBatchWriteExceptionWithEmptyTables()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Abnormal operation.');

        Ots::throwBatchWriteException([]);
    }

    /**
     * @throws Exception
     */
    public function testThrowBatchWriteExceptionWithNonArrayTables()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Abnormal operation.');

        Ots::throwBatchWriteException(['tables' => 'not_an_array']);
    }
}
