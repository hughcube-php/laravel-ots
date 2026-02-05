<?php

namespace HughCube\Laravel\OTS\Tests\Eloquent;

use Aliyun\OTS\Consts\PrimaryKeyTypeConst;
use HughCube\Laravel\OTS\Eloquent\Builder;
use HughCube\Laravel\OTS\Eloquent\Model;
use HughCube\Laravel\OTS\Query\Builder as QueryBuilder;
use HughCube\Laravel\OTS\Tests\TestCase;

class BuilderTestModel extends Model
{
    protected $table = 'builder_test';

    protected $primaryKeyColumns = [
        'id' => PrimaryKeyTypeConst::CONST_STRING,
    ];

    protected $searchIndex = 'builder_test_index';

    protected $fillable = ['id', 'name', 'status'];
}

class BuilderTest extends TestCase
{
    public function testInstanceOf()
    {
        $builder = $this->getEloquentBuilder();
        $this->assertInstanceOf(Builder::class, $builder);
    }

    public function testUseSearchIndex()
    {
        $builder = $this->getEloquentBuilder();
        $result = $builder->useSearchIndex('custom_index');

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testPrimaryKey()
    {
        $builder = $this->getEloquentBuilder();
        $result = $builder->primaryKey(['id' => 'test']);

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testAsync()
    {
        $builder = $this->getEloquentBuilder();
        $result = $builder->async(true);

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testForward()
    {
        $builder = $this->getEloquentBuilder();
        $result = $builder->forward();

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testBackward()
    {
        $builder = $this->getEloquentBuilder();
        $result = $builder->backward();

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testStartKey()
    {
        $builder = $this->getEloquentBuilder();
        $result = $builder->startKey(['id' => 'start']);

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testEndKey()
    {
        $builder = $this->getEloquentBuilder();
        $result = $builder->endKey(['id' => 'end']);

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testExpectExist()
    {
        $builder = $this->getEloquentBuilder();
        $result = $builder->expectExist();

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testExpectNotExist()
    {
        $builder = $this->getEloquentBuilder();
        $result = $builder->expectNotExist();

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testSearchTerm()
    {
        $builder = $this->getEloquentBuilder();
        $result = $builder->searchTerm('status', 'active');

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testSearchTerms()
    {
        $builder = $this->getEloquentBuilder();
        $result = $builder->searchTerms('status', ['active', 'pending']);

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testSearchMatch()
    {
        $builder = $this->getEloquentBuilder();
        $result = $builder->searchMatch('name', 'test');

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testSearchMatchPhrase()
    {
        $builder = $this->getEloquentBuilder();
        $result = $builder->searchMatchPhrase('name', 'test phrase');

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testSearchPrefix()
    {
        $builder = $this->getEloquentBuilder();
        $result = $builder->searchPrefix('name', 'pre');

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testSearchRange()
    {
        $builder = $this->getEloquentBuilder();
        $result = $builder->searchRange('age', 18, 65);

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testSearchWildcard()
    {
        $builder = $this->getEloquentBuilder();
        $result = $builder->searchWildcard('name', 'test*');

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testSearchMatchAll()
    {
        $builder = $this->getEloquentBuilder();
        $result = $builder->searchMatchAll();

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testChainedMethods()
    {
        $builder = $this->getEloquentBuilder();
        $result = $builder
            ->useSearchIndex('custom_index')
            ->searchTerm('status', 'active')
            ->searchRange('age', 18, 65)
            ->select(['id', 'name'])
            ->limit(10);

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function testGetQuery()
    {
        $builder = $this->getEloquentBuilder();
        $query = $builder->getQuery();

        $this->assertInstanceOf(QueryBuilder::class, $query);
    }

    public function testGetModel()
    {
        $builder = $this->getEloquentBuilder();
        $model = $builder->getModel();

        $this->assertInstanceOf(BuilderTestModel::class, $model);
    }

    protected function getEloquentBuilder(): Builder
    {
        return BuilderTestModel::query();
    }
}
