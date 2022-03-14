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
use Aliyun\OTS\Consts\DirectionConst;
use Aliyun\OTS\Consts\OperationTypeConst;
use Aliyun\OTS\Consts\PrimaryKeyTypeConst;
use Aliyun\OTS\Consts\RowExistenceExpectationConst;
use Aliyun\OTS\OTSClientException;
use Aliyun\OTS\OTSServerException;
use Closure;
use Exception;
use HughCube\Laravel\OTS\Connection;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Store as IlluminateStore;
use Illuminate\Support\Collection;
use InvalidArgumentException;

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
    public function getIndexTable(): ?string
    {
        return $this->indexTable;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @inheritDoc
     *
     * @throws OTSServerException
     * @throws OTSClientException
     */
    public function get($key)
    {
        $request = [
            'table_name' => $this->getTable(),
            'primary_key' => $this->makePrimaryKey($key),
            'max_versions' => 1,
        ];

        $response = $this->getOts()->getRow($request);

        return $this->parseValueInOtsResponse($response);
    }

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @inheritDoc
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function put($key, $value, $seconds): bool
    {
        $request = [
            'table_name' => $this->getTable(),
            'condition' => RowExistenceExpectationConst::CONST_IGNORE,
            'primary_key' => $this->makePrimaryKey($key),
            'attribute_columns' => $this->makeAttributeColumns($value, $seconds),
        ];

        $response = $this->getOts()->putRow($request);

        return isset($response['primary_key'], $response['attribute_columns']);
    }

    /**
     * @param  string|int  $key
     * @param  mixed  $value
     * @param  int|null  $seconds
     *
     * @return bool
     * @throws OTSServerException
     *
     * @throws OTSClientException
     */
    public function add($key, $value, int $seconds = null): bool
    {
        $request = [
            'table_name' => $this->getTable(),
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
            $response = $this->getOts()->putRow($request);

            return isset($response['primary_key'], $response['attribute_columns']);
        } catch (OTSServerException $exception) {
            if ('OTSConditionCheckFail' === $exception->getOTSErrorCode()) {
                return false;
            }

            throw $exception;
        }
    }

    /**
     * @inheritDoc
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function many(array $keys): array
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
                    'table_name' => $this->getTable(),
                    'max_versions' => 1,
                    'primary_keys' => $primaryKeys->toArray(),
                ],
            ],
        ];

        $response = $this->getOts()->batchGetRow($request);

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
     * @inheritDoc
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function putMany(array $values, $seconds): bool
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
        $request = ['tables' => [['table_name' => $this->getTable(), 'rows' => $rows->values()->toArray()]]];
        $response = $this->getOts()->batchWriteRow($request);

        $rowResults = [];
        foreach ($response['tables'] as $table) {
            if ($this->getTable() !== $table['table_name']) {
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
     *
     * @return bool
     * @throws OTSServerException
     *
     * @throws OTSClientException
     */
    public function putManyForever(array $values): bool
    {
        return $this->putMany($values, null);
    }

    /**
     * @inheritDoc
     *
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
     * @inheritDoc
     *
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
     * @param  string|int  $key
     * @param  mixed  $value
     * @param  Closure  $callback
     *
     * @return false|mixed
     * @throws OTSServerException
     *
     * @throws OTSClientException
     */
    protected function incrementOrDecrement($key, $value, Closure $callback)
    {
        if (!is_numeric($current = $this->get($key))) {
            return false;
        }

        $new = $callback((int) $current, $value);

        $request = [
            'table_name' => $this->getTable(),
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
            $response = $this->getOts()->updateRow($request);
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
     * @inheritDoc
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function forever($key, $value): bool
    {
        $request = [
            'table_name' => $this->getTable(),
            'condition' => RowExistenceExpectationConst::CONST_IGNORE,
            'primary_key' => $this->makePrimaryKey($key),
            'attribute_columns' => $this->makeAttributeColumns($value),
        ];

        $response = $this->getOts()->putRow($request);

        return isset($response['primary_key'], $response['attribute_columns']);
    }

    /**
     * @inheritDoc
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function forget($key): bool
    {
        $request = [
            'table_name' => $this->getTable(),
            'condition' => RowExistenceExpectationConst::CONST_IGNORE,
            'primary_key' => $this->makePrimaryKey($key),
        ];

        $response = $this->getOts()->deleteRow($request);

        return isset($response['primary_key'], $response['attribute_columns']);
    }

    /**
     * @inheritDoc
     */
    public function flush(): bool
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
        return new Lock($this->getOts(), $this->getTable(), $this->prefix, $name, $seconds, $owner);
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

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function flushExpiredRows($count = 200, $expiredDuration = 24 * 3600)
    {
        $indexTable = $this->getIndexTable();
        if (empty($indexTable)) {
            throw new Exception('Configure the INDEX table first!');
        }

        if ($expiredDuration < 24 * 3600) {
            throw new InvalidArgumentException('At least one day expired before it can be cleaned.');
        }

        /** Query */
        $response = $this->getOts()->getRange([
            'table_name' => $indexTable,
            'max_versions' => 1,
            'direction' => DirectionConst::CONST_FORWARD,
            'inclusive_start_primary_key' => [
                ['expiration', 1],
                ['key', null, PrimaryKeyTypeConst::CONST_INF_MIN],
                ['prefix', null, PrimaryKeyTypeConst::CONST_INF_MIN],
                ['type', null, PrimaryKeyTypeConst::CONST_INF_MIN],
            ],
            'exclusive_end_primary_key' => [
                ['expiration', (time() - $expiredDuration)],
                ['key', null, PrimaryKeyTypeConst::CONST_INF_MAX],
                ['prefix', null, PrimaryKeyTypeConst::CONST_INF_MAX],
                ['type', null, PrimaryKeyTypeConst::CONST_INF_MAX],
            ],
            'limit' => $count,
        ]);
        if (empty($response['rows'])) {
            return 0;
        }

        /** Delete */
        $this->getOts()->batchWriteRow([
            'tables' => [
                [
                    'table_name' => $this->getTable(),
                    'rows' => Collection::make($response['rows'])->map(function ($row) {
                        $row = Collection::make($row['primary_key'])->keyBy(0);
                        return [
                            'operation_type' => OperationTypeConst::CONST_DELETE,
                            'condition' => [
                                'row_existence' => RowExistenceExpectationConst::CONST_IGNORE,
                                /** (Col2 <= 10) */
                                'column_condition' => [
                                    'column_name' => 'expiration',
                                    'value' => [time(), ColumnTypeConst::CONST_INTEGER],
                                    'comparator' => ComparatorTypeConst::CONST_LESS_EQUAL,
                                    'pass_if_missing' => true,
                                    'latest_version_only' => true,
                                ],
                            ],
                            'primary_key' => [
                                $row->get('key'),
                                $row->get('prefix'),
                                $row->get('type'),
                            ],
                        ];
                    })->values()->toArray()
                ],
            ],
        ]);

        return count($response['rows']);
    }
}
