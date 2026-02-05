<?php

namespace HughCube\Laravel\OTS\Tests\Query;

use Aliyun\OTS\Consts\DirectionConst;
use Aliyun\OTS\Consts\PrimaryKeyTypeConst;
use Aliyun\OTS\Consts\QueryTypeConst;
use Aliyun\OTS\Consts\RowExistenceExpectationConst;
use HughCube\Laravel\OTS\Query\Builder;
use HughCube\Laravel\OTS\Tests\TestCase;
use Illuminate\Support\Collection;

class BuilderExtendedTest extends TestCase
{
    public function testPrimaryKeyColumns()
    {
        $builder = $this->getBuilder();
        $builder->primaryKeyColumns(['id' => PrimaryKeyTypeConst::CONST_INTEGER]);

        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('primaryKeyColumns');
        $property->setAccessible(true);

        $this->assertEquals(['id' => PrimaryKeyTypeConst::CONST_INTEGER], $property->getValue($builder));
    }

    public function testPrimaryKey()
    {
        $builder = $this->getBuilder();
        $builder->primaryKey(['id' => 123]);

        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('primaryKeys');
        $property->setAccessible(true);

        $this->assertEquals(['id' => 123], $property->getValue($builder));
    }

    public function testUseSearchIndex()
    {
        $builder = $this->getBuilder();
        $builder->useSearchIndex('test_index');

        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('searchIndex');
        $property->setAccessible(true);

        $this->assertEquals('test_index', $property->getValue($builder));
    }

    public function testAsync()
    {
        $builder = $this->getBuilder();
        $builder->async(true);

        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('async');
        $property->setAccessible(true);

        $this->assertTrue($property->getValue($builder));
    }

    public function testForward()
    {
        $builder = $this->getBuilder();
        $builder->forward();

        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('direction');
        $property->setAccessible(true);

        $this->assertEquals(DirectionConst::CONST_FORWARD, $property->getValue($builder));
    }

    public function testBackward()
    {
        $builder = $this->getBuilder();
        $builder->backward();

        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('direction');
        $property->setAccessible(true);

        $this->assertEquals(DirectionConst::CONST_BACKWARD, $property->getValue($builder));
    }

    public function testStartKey()
    {
        $builder = $this->getBuilder();
        $builder->startKey(['id' => 1]);

        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('startPrimaryKey');
        $property->setAccessible(true);

        $this->assertEquals(['id' => 1], $property->getValue($builder));
    }

    public function testEndKey()
    {
        $builder = $this->getBuilder();
        $builder->endKey(['id' => 100]);

        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('endPrimaryKey');
        $property->setAccessible(true);

        $this->assertEquals(['id' => 100], $property->getValue($builder));
    }

    public function testExpectExist()
    {
        $builder = $this->getBuilder();
        $builder->expectExist();

        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('rowExistence');
        $property->setAccessible(true);

        $this->assertEquals(RowExistenceExpectationConst::CONST_EXPECT_EXIST, $property->getValue($builder));
    }

    public function testExpectNotExist()
    {
        $builder = $this->getBuilder();
        $builder->expectNotExist();

        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('rowExistence');
        $property->setAccessible(true);

        $this->assertEquals(RowExistenceExpectationConst::CONST_EXPECT_NOT_EXIST, $property->getValue($builder));
    }

    public function testSearchTerm()
    {
        $builder = $this->getBuilder();
        $builder->searchTerm('name', 'test');

        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('searchQueries');
        $property->setAccessible(true);

        $queries = $property->getValue($builder);
        $this->assertCount(1, $queries);
        $this->assertEquals(QueryTypeConst::TERM_QUERY, $queries[0]['type']);
        $this->assertEquals('name', $queries[0]['column']);
        $this->assertEquals('test', $queries[0]['value']);
    }

    public function testSearchTerms()
    {
        $builder = $this->getBuilder();
        $builder->searchTerms('status', ['active', 'pending']);

        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('searchQueries');
        $property->setAccessible(true);

        $queries = $property->getValue($builder);
        $this->assertCount(1, $queries);
        $this->assertEquals(QueryTypeConst::TERMS_QUERY, $queries[0]['type']);
        $this->assertEquals('status', $queries[0]['column']);
        $this->assertEquals(['active', 'pending'], $queries[0]['values']);
    }

    public function testSearchMatch()
    {
        $builder = $this->getBuilder();
        $builder->searchMatch('content', 'hello world');

        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('searchQueries');
        $property->setAccessible(true);

        $queries = $property->getValue($builder);
        $this->assertCount(1, $queries);
        $this->assertEquals(QueryTypeConst::MATCH_QUERY, $queries[0]['type']);
    }

    public function testSearchMatchPhrase()
    {
        $builder = $this->getBuilder();
        $builder->searchMatchPhrase('content', 'hello world');

        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('searchQueries');
        $property->setAccessible(true);

        $queries = $property->getValue($builder);
        $this->assertCount(1, $queries);
        $this->assertEquals(QueryTypeConst::MATCH_PHRASE_QUERY, $queries[0]['type']);
    }

    public function testSearchPrefix()
    {
        $builder = $this->getBuilder();
        $builder->searchPrefix('name', 'test');

        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('searchQueries');
        $property->setAccessible(true);

        $queries = $property->getValue($builder);
        $this->assertCount(1, $queries);
        $this->assertEquals(QueryTypeConst::PREFIX_QUERY, $queries[0]['type']);
    }

    public function testSearchRange()
    {
        $builder = $this->getBuilder();
        $builder->searchRange('age', 18, 65, true, false);

        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('searchQueries');
        $property->setAccessible(true);

        $queries = $property->getValue($builder);
        $this->assertCount(1, $queries);
        $this->assertEquals(QueryTypeConst::RANGE_QUERY, $queries[0]['type']);
        $this->assertEquals(18, $queries[0]['from']);
        $this->assertEquals(65, $queries[0]['to']);
        $this->assertTrue($queries[0]['include_from']);
        $this->assertFalse($queries[0]['include_to']);
    }

    public function testSearchWildcard()
    {
        $builder = $this->getBuilder();
        $builder->searchWildcard('name', 'test*');

        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('searchQueries');
        $property->setAccessible(true);

        $queries = $property->getValue($builder);
        $this->assertCount(1, $queries);
        $this->assertEquals(QueryTypeConst::WILDCARD_QUERY, $queries[0]['type']);
    }

    public function testSearchMatchAll()
    {
        $builder = $this->getBuilder();
        $builder->searchMatchAll();

        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('searchQueries');
        $property->setAccessible(true);

        $queries = $property->getValue($builder);
        $this->assertCount(1, $queries);
        $this->assertEquals(QueryTypeConst::MATCH_ALL_QUERY, $queries[0]['type']);
    }

    public function testToSql()
    {
        $builder = $this->getBuilder();
        $builder->from('users')->select(['id', 'name'])->where('status', '=', 'active')->limit(10);

        $sql = $builder->toSql();

        $this->assertStringContainsString('SELECT id, name FROM users', $sql);
        $this->assertStringContainsString('LIMIT 10', $sql);
    }

    public function testChainedMethods()
    {
        $builder = $this->getBuilder();
        $result = $builder
            ->from('users')
            ->useSearchIndex('users_index')
            ->searchTerm('status', 'active')
            ->searchRange('age', 18, 65)
            ->select(['id', 'name'])
            ->limit(10);

        $this->assertInstanceOf(Builder::class, $result);
    }

    protected function getBuilder(): Builder
    {
        $connection = $this->getConnection();

        return new Builder(
            $connection,
            $connection->getQueryGrammar(),
            $connection->getPostProcessor()
        );
    }
}
