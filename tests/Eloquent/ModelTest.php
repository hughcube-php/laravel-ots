<?php

namespace HughCube\Laravel\OTS\Tests\Eloquent;

use Aliyun\OTS\Consts\PrimaryKeyTypeConst;
use HughCube\Laravel\OTS\Eloquent\Builder;
use HughCube\Laravel\OTS\Eloquent\Model;
use HughCube\Laravel\OTS\Query\Builder as QueryBuilder;
use HughCube\Laravel\OTS\Tests\TestCase;

class TestModel extends Model
{
    protected $table = 'test_table';

    protected $primaryKeyColumns = [
        'pk1' => PrimaryKeyTypeConst::CONST_STRING,
        'pk2' => PrimaryKeyTypeConst::CONST_INTEGER,
    ];

    protected $searchIndex = 'test_index';

    protected $fillable = ['pk1', 'pk2', 'name', 'value'];
}

class AutoIncrementModel extends Model
{
    protected $table = 'auto_table';

    protected $primaryKeyColumns = [
        'id' => PrimaryKeyTypeConst::CONST_PK_AUTO_INCR,
    ];

    protected $fillable = ['id', 'name'];
}

class ModelTest extends TestCase
{
    public function testModelInstanceOf()
    {
        $model = new TestModel();
        $this->assertInstanceOf(Model::class, $model);
    }

    public function testGetPrimaryKeyColumns()
    {
        $model = new TestModel();
        $columns = $model->getPrimaryKeyColumns();

        $this->assertIsArray($columns);
        $this->assertArrayHasKey('pk1', $columns);
        $this->assertArrayHasKey('pk2', $columns);
        $this->assertEquals(PrimaryKeyTypeConst::CONST_STRING, $columns['pk1']);
        $this->assertEquals(PrimaryKeyTypeConst::CONST_INTEGER, $columns['pk2']);
    }

    public function testGetSearchIndex()
    {
        $model = new TestModel();
        $this->assertEquals('test_index', $model->getSearchIndex());
    }

    public function testSetSearchIndex()
    {
        $model = new TestModel();
        $model->setSearchIndex('new_index');
        $this->assertEquals('new_index', $model->getSearchIndex());
    }

    public function testGetPrimaryKeyValues()
    {
        $model = new TestModel();
        $model->pk1 = 'test_pk1';
        $model->pk2 = 123;

        $values = $model->getPrimaryKeyValues();

        $this->assertEquals(['pk1' => 'test_pk1', 'pk2' => 123], $values);
    }

    public function testNewEloquentBuilder()
    {
        $model = new TestModel();
        $queryBuilder = new QueryBuilder(
            $this->getConnection(),
            $this->getConnection()->getQueryGrammar(),
            $this->getConnection()->getPostProcessor()
        );

        $builder = $model->newEloquentBuilder($queryBuilder);

        $this->assertInstanceOf(Builder::class, $builder);
    }

    public function testTimestampsDisabled()
    {
        $model = new TestModel();
        $this->assertFalse($model->usesTimestamps());
    }

    public function testIncrementingDisabled()
    {
        $model = new TestModel();
        $this->assertFalse($model->getIncrementing());
    }

    public function testConnectionName()
    {
        $model = new TestModel();
        $this->assertEquals('ots', $model->getConnectionName());
    }

    public function testFillable()
    {
        $model = new TestModel(['pk1' => 'test', 'pk2' => 1, 'name' => 'Test Name']);

        $this->assertEquals('test', $model->pk1);
        $this->assertEquals(1, $model->pk2);
        $this->assertEquals('Test Name', $model->name);
    }

    public function testAutoIncrementModel()
    {
        $model = new AutoIncrementModel();
        $columns = $model->getPrimaryKeyColumns();

        $this->assertEquals(PrimaryKeyTypeConst::CONST_PK_AUTO_INCR, $columns['id']);
    }

    public function testQuery()
    {
        $builder = TestModel::query();

        $this->assertInstanceOf(Builder::class, $builder);
    }

    public function testNewQuery()
    {
        $model = new TestModel();
        $builder = $model->newQuery();

        $this->assertInstanceOf(Builder::class, $builder);
    }

    public function testAttributeAccess()
    {
        $model = new TestModel();
        $model->name = 'Test';
        $model->value = 'Value';

        $this->assertEquals('Test', $model->name);
        $this->assertEquals('Value', $model->value);
    }

    public function testToArray()
    {
        $model = new TestModel([
            'pk1' => 'test',
            'pk2' => 1,
            'name' => 'Test Name',
        ]);

        $array = $model->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('test', $array['pk1']);
        $this->assertEquals(1, $array['pk2']);
        $this->assertEquals('Test Name', $array['name']);
    }

    public function testNewInstance()
    {
        $model = new TestModel();
        $newModel = $model->newInstance(['pk1' => 'new', 'pk2' => 2]);

        $this->assertInstanceOf(TestModel::class, $newModel);
        $this->assertEquals('new', $newModel->pk1);
        $this->assertEquals(2, $newModel->pk2);
    }

    public function testNewCollection()
    {
        $model = new TestModel();
        $collection = $model->newCollection([
            new TestModel(['pk1' => 'a', 'pk2' => 1]),
            new TestModel(['pk1' => 'b', 'pk2' => 2]),
        ]);

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $collection);
    }
}
