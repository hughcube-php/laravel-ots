<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/8
 * Time: 5:56 下午.
 */

namespace HughCube\Laravel\OTS\Cache;

use Aliyun\OTS\Consts\ColumnTypeConst;
use Aliyun\OTS\Consts\ComparatorTypeConst;
use Aliyun\OTS\Consts\OperationTypeConst;
use Aliyun\OTS\Consts\RowExistenceExpectationConst;
use Aliyun\OTS\OTSClientException;
use Aliyun\OTS\OTSServerException;
use Closure;
use HughCube\Laravel\OTS\Connection;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Store as IlluminateStore;
use Illuminate\Support\Collection;

class Store extends TaggableStore implements IlluminateStore, LockProvider
{
    use Attribute;

    /**
     * @var string
     */
    protected $indexTable;

    /**
     * Store constructor.
     *
     * @param  Connection  $ots
     * @param  string  $table
     * @param  string  $prefix
     * @param  string|null  $indexTable
     */
    public function __construct(Connection $ots, string $table, string $prefix, ?string $indexTable)
    {
        $this->ots = $ots;
        $this->table = $table;
        $this->prefix = $prefix;
        $this->type = 'cache';
        $this->indexTable = $indexTable;
    }

    /**
     * @return string|null
     */
    public function getIndexTable()
    {
        return $this->indexTable;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param $key
     * @return mixed|null
     * @throws OTSServerException
     * @throws OTSClientException
     */
    public function get($key)
    {
        $request = [
            'table_name' => $this->table,
            'primary_key' => $this->makePrimaryKey($key),
            'max_versions' => 1,
        ];

        $response = $this->ots->getRow($request);

        return $this->parseValueInOtsResponse($response);
    }

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param $key
     * @param $value
     * @param $seconds
     * @return bool
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function put($key, $value, $seconds)
    {
        $request = [
            'table_name' => $this->table,
            'condition' => RowExistenceExpectationConst::CONST_IGNORE,
            'primary_key' => $this->makePrimaryKey($key),
            'attribute_columns' => $this->makeAttributeColumns($value, $seconds),
        ];

        $response = $this->ots->putRow($request);

        return isset($response['primary_key'], $response['attribute_columns']);
    }

    /**
     * @param $key
     * @param $value
     * @param $seconds
     * @return bool
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function add($key, $value, $seconds = null)
    {
        $request = [
            'table_name' => $this->table,
            'condition' => [
                'row_existence' => RowExistenceExpectationConst::CONST_IGNORE,
                /** (Col2 <= 10) */
                'column_condition' => [
                    'column_name' => 'expiration',
                    'value' => [$this->currentTime(), ColumnTypeConst::CONST_INTEGER],
                    'comparator' => ComparatorTypeConst::CONST_LESS_EQUAL,
                    'pass_if_missing' => true,
                    'latest_version_only' => true,
                ],
            ],
            'primary_key' => $this->makePrimaryKey($key),
            'attribute_columns' => $this->makeAttributeColumns($value, $seconds),
        ];

        try {
            $response = $this->ots->putRow($request);

            return isset($response['primary_key'], $response['attribute_columns']);
        } catch (OTSServerException $exception) {
            if ('OTSConditionCheckFail' === $exception->getOTSErrorCode()) {
                return false;
            }
            throw $exception;
        }
    }

    /**
     * @param  array  $keys
     * @return array
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function many(array $keys)
    {
        if (empty($keys)) {
            return [];
        }

        $primaryKeys = Collection::make($keys)->values()->map(function ($key) {
            return $this->makePrimaryKey($key);
        });

        $request = [
            'tables' => [
                [
                    'table_name' => $this->table, 'max_versions' => 1, 'primary_keys' => $primaryKeys->toArray()
                ]
            ],
        ];

        $response = $this->ots->batchGetRow($request);

        $results = Collection::make($keys)->values()->mapWithKeys(function ($key) {
            return [$key => null];
        })->toArray();

        foreach ($response['tables'] as $table) {
            if ('cache' !== $table['table_name']) {
                continue;
            }

            foreach ($table['rows'] as $row) {
                if (!$row['is_ok']) {
                    continue;
                }

                if (!is_null($key = $this->parseKeyInOtsResponse($row))) {
                    $results[$key] = $this->parseValueInOtsResponse($row);
                }
            }
        }

        return $results;
    }

    /**
     * @param  array  $values
     * @param $seconds
     * @return bool
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function putMany(array $values, $seconds)
    {
        if (empty($values)) {
            return true;
        }

        $rows = Collection::make($values)->map(function ($value, $key) use ($seconds) {
            return [
                'operation_type' => OperationTypeConst::CONST_PUT,
                'condition' => RowExistenceExpectationConst::CONST_IGNORE,
                'primary_key' => $this->makePrimaryKey($key),
                'attribute_columns' => $this->makeAttributeColumns($value, $seconds),
            ];
        });
        $request = ['tables' => [['table_name' => $this->table, 'rows' => $rows->values()->toArray()]]];
        $response = $this->ots->batchWriteRow($request);

        $rowResults = [];
        foreach ($response['tables'] as $table) {
            if ($this->table !== $table['table_name']) {
                continue;
            }
            foreach ($table['rows'] as $row) {
                $rowResults[] = $row['is_ok'];
            }
        }

        return !empty($rowResults) && !in_array(false, $rowResults, true);
    }

    /**
     * @param  array  $values
     * @return bool
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function putManyForever(array $values)
    {
        return $this->putMany($values, null);
    }

    /**
     * @param $key
     * @param  int|float  $value
     * @return bool|int|mixed
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function increment($key, $value = 1)
    {
        return $this->incrementOrDecrement($key, $value, function ($current, $value) {
            return $current + $value;
        });
    }

    /**
     * @param $key
     * @param  int|float  $value
     * @return bool|int|mixed
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function decrement($key, $value = 1)
    {
        return $this->incrementOrDecrement($key, $value, function ($current, $value) {
            return $current - $value;
        });
    }

    /**
     * @param $key
     * @param $value
     * @param  Closure  $callback
     * @return false|mixed
     * @throws OTSClientException
     * @throws OTSServerException
     */
    protected function incrementOrDecrement($key, $value, Closure $callback)
    {
        if (!is_numeric($current = $this->get($key))) {
            return false;
        }

        $new = $callback((int) $current, $value);

        $request = [
            'table_name' => $this->table,
            'condition' => [
                'row_existence' => RowExistenceExpectationConst::CONST_EXPECT_EXIST,
                'column_condition' => [
                    'column_name' => 'value',
                    'value' => [$this->serialize($current), ColumnTypeConst::CONST_BINARY],
                    'comparator' => ComparatorTypeConst::CONST_EQUAL,
                ],
            ],
            'primary_key' => $this->makePrimaryKey($key),
            'update_of_attribute_columns' => [
                'PUT' => $this->makeAttributeColumns($new),
            ],
        ];

        try {
            $response = $this->ots->updateRow($request);
            if (isset($response['primary_key'], $response['attribute_columns'])) {
                return $new;
            }
        } catch (OTSServerException $exception) {
            if ('OTSConditionCheckFail' !== $exception->getOTSErrorCode()) {
                throw $exception;
            }
        }

        return false;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function forever($key, $value)
    {
        $request = [
            'table_name' => $this->table,
            'condition' => RowExistenceExpectationConst::CONST_IGNORE,
            'primary_key' => $this->makePrimaryKey($key),
            'attribute_columns' => $this->makeAttributeColumns($value),
        ];

        $response = $this->ots->putRow($request);

        return isset($response['primary_key'], $response['attribute_columns']);
    }

    /**
     * @inheritDoc
     */
    public function forget($key)
    {
        $request = [
            'table_name' => $this->table,
            'condition' => RowExistenceExpectationConst::CONST_IGNORE,
            'primary_key' => $this->makePrimaryKey($key),
        ];

        $response = $this->ots->deleteRow($request);

        return isset($response['primary_key'], $response['attribute_columns']);
    }

    /**
     * @inheritDoc
     */
    public function flush()
    {
        return true;
    }

    /**
     * Get a lock instance.
     *
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     *
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function lock($name, $seconds = 0, $owner = null)
    {
        return new Lock($this->ots, $this->table, $this->prefix, $name, $seconds, $owner);
    }

    /**
     * Restore a lock instance using the owner identifier.
     *
     * @param  string  $name
     * @param  string  $owner
     *
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function restoreLock($name, $owner)
    {
        return $this->lock($name, 0, $owner);
    }
}
