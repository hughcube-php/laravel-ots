<?php

namespace HughCube\Laravel\OTS\Tests\Stream;

use HughCube\Laravel\OTS\Stream\StreamRecord;
use HughCube\Laravel\OTS\Tests\TestCase;

class StreamRecordTest extends TestCase
{
    public function testInstanceOf()
    {
        $record = new StreamRecord([
            'action_type' => 'PUT_ROW',
            'primary_key' => [['pk1', 'value1']],
            'attribute_columns' => [['col1', 'data1', 'STRING', 1234567890]],
        ]);

        $this->assertInstanceOf(StreamRecord::class, $record);
    }

    public function testGetActionType()
    {
        $record = new StreamRecord(['action_type' => 'PUT_ROW']);
        $this->assertEquals('PUT_ROW', $record->getActionType());
    }

    public function testIsPut()
    {
        $putRecord = new StreamRecord(['action_type' => 'PUT_ROW']);
        $updateRecord = new StreamRecord(['action_type' => 'UPDATE_ROW']);

        $this->assertTrue($putRecord->isPut());
        $this->assertFalse($updateRecord->isPut());
    }

    public function testIsUpdate()
    {
        $updateRecord = new StreamRecord(['action_type' => 'UPDATE_ROW']);
        $putRecord = new StreamRecord(['action_type' => 'PUT_ROW']);

        $this->assertTrue($updateRecord->isUpdate());
        $this->assertFalse($putRecord->isUpdate());
    }

    public function testIsDelete()
    {
        $deleteRecord = new StreamRecord(['action_type' => 'DELETE_ROW']);
        $putRecord = new StreamRecord(['action_type' => 'PUT_ROW']);

        $this->assertTrue($deleteRecord->isDelete());
        $this->assertFalse($putRecord->isDelete());
    }

    public function testGetPrimaryKey()
    {
        $record = new StreamRecord([
            'action_type' => 'PUT_ROW',
            'primary_key' => [
                ['pk1', 'value1'],
                ['pk2', 123],
            ],
        ]);

        $pk = $record->getPrimaryKey();

        $this->assertEquals(['pk1' => 'value1', 'pk2' => 123], $pk);
    }

    public function testGetPrimaryKeyValue()
    {
        $record = new StreamRecord([
            'action_type' => 'PUT_ROW',
            'primary_key' => [['pk1', 'value1']],
        ]);

        $this->assertEquals('value1', $record->getPrimaryKeyValue('pk1'));
        $this->assertNull($record->getPrimaryKeyValue('nonexistent'));
    }

    public function testGetAttributes()
    {
        $record = new StreamRecord([
            'action_type' => 'PUT_ROW',
            'attribute_columns' => [
                ['col1', 'data1', 'STRING', 1234567890],
                ['col2', 123, 'INTEGER', 1234567891],
            ],
        ]);

        $attrs = $record->getAttributes();

        $this->assertCount(2, $attrs);
        $this->assertArrayHasKey('col1', $attrs);
        $this->assertArrayHasKey('col2', $attrs);
    }

    public function testGetAttribute()
    {
        $record = new StreamRecord([
            'action_type' => 'PUT_ROW',
            'attribute_columns' => [
                ['col1', 'data1', 'STRING', 1234567890],
            ],
        ]);

        $this->assertEquals('data1', $record->getAttribute('col1'));
        $this->assertNull($record->getAttribute('nonexistent'));
    }

    public function testGetAttributeWithMeta()
    {
        $record = new StreamRecord([
            'action_type' => 'PUT_ROW',
            'attribute_columns' => [
                ['col1', 'data1', 'STRING', 1234567890],
            ],
        ]);

        $meta = $record->getAttributeWithMeta('col1');

        $this->assertEquals('data1', $meta['value']);
        $this->assertEquals('STRING', $meta['type']);
        $this->assertEquals(1234567890, $meta['timestamp']);
    }

    public function testToArray()
    {
        $record = new StreamRecord([
            'action_type' => 'PUT_ROW',
            'primary_key' => [['pk1', 'value1']],
            'attribute_columns' => [
                ['col1', 'data1', 'STRING', 1234567890],
            ],
        ]);

        $array = $record->toArray();

        $this->assertEquals([
            'pk1' => 'value1',
            'col1' => 'data1',
        ], $array);
    }

    public function testGetRaw()
    {
        $rawData = [
            'action_type' => 'PUT_ROW',
            'primary_key' => [['pk1', 'value1']],
        ];

        $record = new StreamRecord($rawData);

        $this->assertEquals($rawData, $record->getRaw());
    }
}
