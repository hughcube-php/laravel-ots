<?php

namespace HughCube\Laravel\OTS\Query;

use Aliyun\OTS\Consts\ColumnReturnTypeConst;
use Aliyun\OTS\Consts\DirectionConst;
use Aliyun\OTS\Consts\PrimaryKeyTypeConst;
use Aliyun\OTS\Consts\QueryTypeConst;
use Aliyun\OTS\Consts\RowExistenceExpectationConst;
use Aliyun\OTS\OTSClientException;
use Aliyun\OTS\OTSServerException;
use HughCube\Laravel\OTS\Connection;
use HughCube\Laravel\OTS\OTS\Handlers\RequestContext;
use Illuminate\Database\Query\Builder as IlluminateBuilder;
use Illuminate\Support\Collection;

/**
 * @property Connection $connection
 *
 * @method Connection getConnection()
 *
 * @phpstan-consistent-constructor
 */
class Builder extends IlluminateBuilder
{
    /**
     * Primary keys for the query.
     *
     * @var array
     */
    protected $primaryKeys = [];

    /**
     * Primary key columns definition.
     *
     * @var array
     */
    protected $primaryKeyColumns = [];

    /**
     * Attribute columns to return.
     *
     * @var array
     */
    protected $attributeColumns = [];

    /**
     * Search index name for search queries.
     *
     * @var string|null
     */
    protected $searchIndex = null;

    /**
     * Search query conditions.
     *
     * @var array
     */
    protected $searchQueries = [];

    /**
     * Bool query must conditions.
     *
     * @var array
     */
    protected $searchMustQueries = [];

    /**
     * Bool query should conditions.
     *
     * @var array
     */
    protected $searchShouldQueries = [];

    /**
     * Bool query must_not conditions.
     *
     * @var array
     */
    protected $searchMustNotQueries = [];

    /**
     * Bool query filter conditions.
     *
     * @var array
     */
    protected $searchFilterQueries = [];

    /**
     * Minimum should match for bool query.
     *
     * @var int|null
     */
    protected $minimumShouldMatch = null;

    /**
     * Use async mode.
     *
     * @var bool
     */
    protected $async = false;

    /**
     * Range query start key.
     *
     * @var array|null
     */
    protected $startPrimaryKey = null;

    /**
     * Range query end key.
     *
     * @var array|null
     */
    protected $endPrimaryKey = null;

    /**
     * Range query direction.
     *
     * @var string
     */
    protected $direction = DirectionConst::CONST_FORWARD;

    /**
     * Row existence condition for write operations.
     *
     * @var string
     */
    protected $rowExistence = RowExistenceExpectationConst::CONST_IGNORE;

    /**
     * Set primary key columns definition.
     *
     * @return $this
     */
    public function primaryKeyColumns(array $columns)
    {
        $this->primaryKeyColumns = $columns;

        return $this;
    }

    /**
     * Set primary keys for query.
     *
     * @return $this
     */
    public function primaryKey(array $keys)
    {
        $this->primaryKeys = $keys;

        return $this;
    }

    /**
     * Set attribute columns to return.
     *
     * @return $this
     */
    public function attributeColumns(array $columns)
    {
        $this->attributeColumns = $columns;

        return $this;
    }

    /**
     * Use search index for query.
     *
     * @return $this
     */
    public function useSearchIndex(string $indexName)
    {
        $this->searchIndex = $indexName;

        return $this;
    }

    /**
     * Enable async mode.
     *
     * @return $this
     */
    public function async(bool $async = true)
    {
        $this->async = $async;

        return $this;
    }

    /**
     * Set range query direction.
     *
     * @return $this
     */
    public function forward()
    {
        $this->direction = DirectionConst::CONST_FORWARD;

        return $this;
    }

    /**
     * Set range query direction to backward.
     *
     * @return $this
     */
    public function backward()
    {
        $this->direction = DirectionConst::CONST_BACKWARD;

        return $this;
    }

    /**
     * Set range query start key.
     *
     * @return $this
     */
    public function startKey(array $key)
    {
        $this->startPrimaryKey = $key;

        return $this;
    }

    /**
     * Set range query end key.
     *
     * @return $this
     */
    public function endKey(array $key)
    {
        $this->endPrimaryKey = $key;

        return $this;
    }

    /**
     * Set row existence condition for write operations.
     *
     * @return $this
     */
    public function rowExistence(string $condition)
    {
        $this->rowExistence = $condition;

        return $this;
    }

    /**
     * Expect row to exist.
     *
     * @return $this
     */
    public function expectExist()
    {
        $this->rowExistence = RowExistenceExpectationConst::CONST_EXPECT_EXIST;

        return $this;
    }

    /**
     * Expect row not to exist.
     *
     * @return $this
     */
    public function expectNotExist()
    {
        $this->rowExistence = RowExistenceExpectationConst::CONST_EXPECT_NOT_EXIST;

        return $this;
    }

    /**
     * Add a search query condition.
     *
     * @param mixed $operator
     * @param mixed $value
     *
     * @return $this
     */
    public function search(string $column, $operator, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->searchQueries[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Add a term search query.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function searchTerm(string $column, $value)
    {
        $this->searchQueries[] = [
            'type' => QueryTypeConst::TERM_QUERY,
            'column' => $column,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Add a terms search query (IN).
     *
     * @return $this
     */
    public function searchTerms(string $column, array $values)
    {
        $this->searchQueries[] = [
            'type' => QueryTypeConst::TERMS_QUERY,
            'column' => $column,
            'values' => $values,
        ];

        return $this;
    }

    /**
     * Add a match search query.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function searchMatch(string $column, $value)
    {
        $this->searchQueries[] = [
            'type' => QueryTypeConst::MATCH_QUERY,
            'column' => $column,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Add a match phrase search query.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function searchMatchPhrase(string $column, $value)
    {
        $this->searchQueries[] = [
            'type' => QueryTypeConst::MATCH_PHRASE_QUERY,
            'column' => $column,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Add a prefix search query.
     *
     * @return $this
     */
    public function searchPrefix(string $column, string $prefix)
    {
        $this->searchQueries[] = [
            'type' => QueryTypeConst::PREFIX_QUERY,
            'column' => $column,
            'value' => $prefix,
        ];

        return $this;
    }

    /**
     * Add a range search query.
     *
     * @param mixed $from
     * @param mixed $to
     *
     * @return $this
     */
    public function searchRange(
        string $column,
        $from = null,
        $to = null,
        bool $includeFrom = true,
        bool $includeTo = false
    ) {
        $this->searchQueries[] = [
            'type' => QueryTypeConst::RANGE_QUERY,
            'column' => $column,
            'from' => $from,
            'to' => $to,
            'include_from' => $includeFrom,
            'include_to' => $includeTo,
        ];

        return $this;
    }

    /**
     * Add a wildcard search query.
     *
     * @return $this
     */
    public function searchWildcard(string $column, string $pattern)
    {
        $this->searchQueries[] = [
            'type' => QueryTypeConst::WILDCARD_QUERY,
            'column' => $column,
            'value' => $pattern,
        ];

        return $this;
    }

    /**
     * Add a match all search query.
     *
     * @return $this
     */
    public function searchMatchAll()
    {
        $this->searchQueries[] = [
            'type' => QueryTypeConst::MATCH_ALL_QUERY,
        ];

        return $this;
    }

    /**
     * Add an exists query (check if field exists).
     *
     * @return $this
     */
    public function searchExists(string $column)
    {
        $this->searchQueries[] = [
            'type' => QueryTypeConst::EXISTS_QUERY,
            'column' => $column,
        ];

        return $this;
    }

    /**
     * Add a nested query.
     *
     * @param callable|array $query Callback to build nested query or raw query array
     *
     * @return $this
     */
    public function searchNested(string $path, $query, ?float $scoreMode = null)
    {
        $nestedQuery = [
            'type' => QueryTypeConst::NESTED_QUERY,
            'path' => $path,
        ];

        if (is_callable($query)) {
            $builder = new static($this->connection, $this->grammar, $this->processor);
            $query($builder);
            $nestedQuery['query'] = $builder->buildSearchQuery();
        } else {
            $nestedQuery['query'] = $query;
        }

        if ($scoreMode !== null) {
            $nestedQuery['score_mode'] = $scoreMode;
        }

        $this->searchQueries[] = $nestedQuery;

        return $this;
    }

    /**
     * Add a geo distance query.
     *
     * @param float $lat       Center latitude
     * @param float $lon       Center longitude
     * @param float $distance  Distance in meters
     *
     * @return $this
     */
    public function searchGeoDistance(string $column, float $lat, float $lon, float $distance)
    {
        $this->searchQueries[] = [
            'type' => QueryTypeConst::GEO_DISTANCE_QUERY,
            'column' => $column,
            'center_point' => "{$lat},{$lon}",
            'distance' => $distance,
        ];

        return $this;
    }

    /**
     * Add a geo bounding box query.
     *
     * @param float $topLeftLat     Top-left corner latitude
     * @param float $topLeftLon     Top-left corner longitude
     * @param float $bottomRightLat Bottom-right corner latitude
     * @param float $bottomRightLon Bottom-right corner longitude
     *
     * @return $this
     */
    public function searchGeoBoundingBox(
        string $column,
        float $topLeftLat,
        float $topLeftLon,
        float $bottomRightLat,
        float $bottomRightLon
    ) {
        $this->searchQueries[] = [
            'type' => QueryTypeConst::GEO_BOUNDING_BOX_QUERY,
            'column' => $column,
            'top_left' => "{$topLeftLat},{$topLeftLon}",
            'bottom_right' => "{$bottomRightLat},{$bottomRightLon}",
        ];

        return $this;
    }

    /**
     * Add a geo polygon query.
     *
     * @param array $points Array of [lat, lon] points defining the polygon
     *
     * @return $this
     */
    public function searchGeoPolygon(string $column, array $points)
    {
        $formattedPoints = array_map(function ($point) {
            return "{$point[0]},{$point[1]}";
        }, $points);

        $this->searchQueries[] = [
            'type' => QueryTypeConst::GEO_POLYGON_QUERY,
            'column' => $column,
            'points' => $formattedPoints,
        ];

        return $this;
    }

    /**
     * Add a must (AND) condition to bool query.
     *
     * @param callable|array $query Callback to build query or raw query array
     *
     * @return $this
     */
    public function searchMust($query)
    {
        if (is_callable($query)) {
            $builder = new static($this->connection, $this->grammar, $this->processor);
            $query($builder);
            $this->searchMustQueries[] = $builder->buildSearchQuery();
        } else {
            $this->searchMustQueries[] = $query;
        }

        return $this;
    }

    /**
     * Add a should (OR) condition to bool query.
     *
     * @param callable|array $query Callback to build query or raw query array
     *
     * @return $this
     */
    public function searchShould($query)
    {
        if (is_callable($query)) {
            $builder = new static($this->connection, $this->grammar, $this->processor);
            $query($builder);
            $this->searchShouldQueries[] = $builder->buildSearchQuery();
        } else {
            $this->searchShouldQueries[] = $query;
        }

        return $this;
    }

    /**
     * Add a must_not (NOT) condition to bool query.
     *
     * @param callable|array $query Callback to build query or raw query array
     *
     * @return $this
     */
    public function searchMustNot($query)
    {
        if (is_callable($query)) {
            $builder = new static($this->connection, $this->grammar, $this->processor);
            $query($builder);
            $this->searchMustNotQueries[] = $builder->buildSearchQuery();
        } else {
            $this->searchMustNotQueries[] = $query;
        }

        return $this;
    }

    /**
     * Add a filter condition to bool query (no scoring).
     *
     * @param callable|array $query Callback to build query or raw query array
     *
     * @return $this
     */
    public function searchFilter($query)
    {
        if (is_callable($query)) {
            $builder = new static($this->connection, $this->grammar, $this->processor);
            $query($builder);
            $this->searchFilterQueries[] = $builder->buildSearchQuery();
        } else {
            $this->searchFilterQueries[] = $query;
        }

        return $this;
    }

    /**
     * Set minimum should match for bool query.
     *
     * @return $this
     */
    public function minimumShouldMatch(int $value)
    {
        $this->minimumShouldMatch = $value;

        return $this;
    }

    /**
     * Build a complete bool query with all conditions.
     *
     * @return $this
     */
    public function searchBool(callable $callback)
    {
        $callback($this);

        return $this;
    }

    /**
     * Get a single row by primary key.
     *
     * @return array|null|RequestContext Returns RequestContext in async mode
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function findByPrimaryKey(array $primaryKey)
    {
        $request = [
            'table_name' => $this->from,
            'primary_key' => $this->formatPrimaryKey($primaryKey),
            'max_versions' => 1,
        ];

        if (!empty($this->columns) && $this->columns !== ['*']) {
            $request['columns_to_get'] = $this->columns;
        }

        if ($this->async) {
            return $this->connection->asyncDoHandle('GetRow', $request);
        }

        $response = $this->connection->getRow($request);

        return $this->parseRow($response);
    }

    /**
     * Get multiple rows by primary keys.
     *
     * @return Collection|RequestContext Returns RequestContext in async mode
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function findMany(array $primaryKeys)
    {
        $request = [
            'tables' => [
                [
                    'table_name' => $this->from,
                    'primary_keys' => array_map(function ($pk) {
                        return $this->formatPrimaryKey($pk);
                    }, $primaryKeys),
                    'max_versions' => 1,
                ],
            ],
        ];

        if (!empty($this->columns) && $this->columns !== ['*']) {
            $request['tables'][0]['columns_to_get'] = $this->columns;
        }

        if ($this->async) {
            return $this->connection->asyncDoHandle('BatchGetRow', $request);
        }

        $response = $this->connection->batchGetRow($request);

        $rows = [];
        foreach ($response['tables'][0]['rows'] ?? [] as $row) {
            if (!empty($row['is_ok'])) {
                $parsed = $this->parseRow($row);
                if ($parsed !== null) {
                    $rows[] = $parsed;
                }
            }
        }

        return new Collection($rows);
    }

    /**
     * Get rows by range.
     *
     * @return Collection|RequestContext Returns RequestContext in async mode
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function getRange()
    {
        $request = [
            'table_name' => $this->from,
            'inclusive_start_primary_key' => $this->formatPrimaryKey($this->startPrimaryKey ?? []),
            'exclusive_end_primary_key' => $this->formatPrimaryKey($this->endPrimaryKey ?? []),
            'direction' => $this->direction,
            'max_versions' => 1,
        ];

        if ($this->limit) {
            $request['limit'] = $this->limit;
        }

        if (!empty($this->columns) && $this->columns !== ['*']) {
            $request['columns_to_get'] = $this->columns;
        }

        if ($this->async) {
            return $this->connection->asyncDoHandle('GetRange', $request);
        }

        $response = $this->connection->getRange($request);

        $rows = [];
        foreach ($response['rows'] ?? [] as $row) {
            $parsed = $this->parseRow(['row' => $row]);
            if ($parsed !== null) {
                $rows[] = $parsed;
            }
        }

        return new Collection($rows);
    }

    /**
     * Execute a search query using search index.
     *
     * @return Collection|RequestContext Returns RequestContext in async mode
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function searchQuery()
    {
        if (empty($this->searchIndex)) {
            throw new OTSClientException('Search index name is required for search queries.');
        }

        $query = $this->buildSearchQuery();

        $request = [
            'table_name' => $this->from,
            'index_name' => $this->searchIndex,
            'search_query' => [
                'offset' => $this->offset ?? 0,
                'limit' => $this->limit ?? 10,
                'get_total_count' => true,
                'query' => $query,
            ],
            'columns_to_get' => [
                'return_type' => ColumnReturnTypeConst::RETURN_ALL,
            ],
        ];

        if (!empty($this->columns) && $this->columns !== ['*']) {
            $request['columns_to_get'] = [
                'return_type' => ColumnReturnTypeConst::RETURN_SPECIFIED,
                'return_names' => $this->columns,
            ];
        }

        if ($this->async) {
            return $this->connection->asyncSearch($request);
        }

        $response = $this->connection->search($request);

        $rows = [];
        foreach ($response['rows'] ?? [] as $row) {
            $parsed = $this->parseRow(['row' => $row]);
            if ($parsed !== null) {
                $rows[] = $parsed;
            }
        }

        return new Collection($rows);
    }

    /**
     * Async search query.
     *
     * @return RequestContext
     */
    public function asyncSearchQuery()
    {
        if (empty($this->searchIndex)) {
            throw new OTSClientException('Search index name is required for search queries.');
        }

        $query = $this->buildSearchQuery();

        $request = [
            'table_name' => $this->from,
            'index_name' => $this->searchIndex,
            'search_query' => [
                'offset' => $this->offset ?? 0,
                'limit' => $this->limit ?? 10,
                'get_total_count' => true,
                'query' => $query,
            ],
            'columns_to_get' => [
                'return_type' => ColumnReturnTypeConst::RETURN_ALL,
            ],
        ];

        if (!empty($this->columns) && $this->columns !== ['*']) {
            $request['columns_to_get'] = [
                'return_type' => ColumnReturnTypeConst::RETURN_SPECIFIED,
                'return_names' => $this->columns,
            ];
        }

        return $this->connection->asyncSearch($request);
    }

    /**
     * Execute SQL query.
     *
     * @return array|RequestContext Returns RequestContext in async mode
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function sql(string $query)
    {
        $request = [
            'query' => $query,
        ];

        if ($this->async) {
            return $this->connection->asyncSqlQuery($request);
        }

        return $this->connection->sqlQuery($request);
    }

    /**
     * Async SQL query.
     *
     * @return RequestContext
     */
    public function asyncSql(string $query)
    {
        return $this->connection->asyncSqlQuery([
            'query' => $query,
        ]);
    }

    /**
     * Build SQL select query from builder state.
     *
     * @return string
     */
    public function toSql()
    {
        $columns = empty($this->columns) || $this->columns === ['*'] ? '*' : implode(', ', $this->columns);
        $sql = "SELECT {$columns} FROM {$this->from}";

        if (!empty($this->wheres)) {
            $sql .= ' WHERE '.$this->compileWheres();
        }

        if ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * Execute SQL query built from builder state.
     *
     * @return array
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function sqlGet()
    {
        return $this->sql($this->toSql());
    }

    /**
     * Insert a new row.
     *
     * @return bool|RequestContext
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function insert(array $values)
    {
        $primaryKey = [];
        $attributeColumns = [];

        foreach ($values as $key => $value) {
            if (in_array($key, array_keys($this->primaryKeyColumns))) {
                $pkType = $this->primaryKeyColumns[$key] ?? null;
                if ($pkType === PrimaryKeyTypeConst::CONST_PK_AUTO_INCR) {
                    $primaryKey[] = [$key, null, $pkType];
                } else {
                    $primaryKey[] = [$key, $value];
                }
            } else {
                $attributeColumns[] = [$key, $value];
            }
        }

        // If primary key columns are defined but not in values, add them from primaryKeys
        if (!empty($this->primaryKeys)) {
            $primaryKey = $this->formatPrimaryKey($this->primaryKeys);
            $attributeColumns = [];
            foreach ($values as $key => $value) {
                if (!isset($this->primaryKeys[$key])) {
                    $attributeColumns[] = [$key, $value];
                }
            }
        }

        $request = [
            'table_name' => $this->from,
            'condition' => $this->rowExistence,
            'primary_key' => $primaryKey,
            'attribute_columns' => $attributeColumns,
        ];

        if ($this->async) {
            return $this->connection->asyncDoHandle('PutRow', $request);
        }

        $response = $this->connection->putRow($request);

        return is_array($response);
    }

    /**
     * Async insert a new row.
     *
     * @return RequestContext
     *
     * @throws OTSClientException
     */
    public function asyncInsert(array $values)
    {
        return $this->async()->insert($values);
    }

    /**
     * Insert a new row and return the auto-increment ID.
     *
     * @param string|null $sequence
     *
     * @return int|null
     *
     * @throws OTSClientException
     * @throws OTSServerException
     * @throws \Exception
     */
    public function insertGetId(array $values, $sequence = null)
    {
        $primaryKey = [];
        $attributeColumns = [];
        $autoIncrementKey = $sequence;

        foreach ($this->primaryKeyColumns as $key => $type) {
            if ($type === PrimaryKeyTypeConst::CONST_PK_AUTO_INCR) {
                $primaryKey[] = [$key, null, $type];
                $autoIncrementKey = $autoIncrementKey ?? $key;
            } elseif (isset($values[$key])) {
                $primaryKey[] = [$key, $values[$key]];
            }
        }

        foreach ($values as $key => $value) {
            if (!isset($this->primaryKeyColumns[$key])) {
                $attributeColumns[] = [$key, $value];
            }
        }

        $request = [
            'table_name' => $this->from,
            'condition' => $this->rowExistence,
            'primary_key' => $primaryKey,
            'attribute_columns' => $attributeColumns,
            'return_content' => [
                'return_type' => 1, // RETURN_PK
            ],
        ];

        $response = $this->connection->putRow($request);

        if ($autoIncrementKey && isset($response['primary_key'])) {
            foreach ($response['primary_key'] as $pk) {
                if ($pk[0] === $autoIncrementKey) {
                    return $pk[1];
                }
            }
        }

        return null;
    }

    /**
     * Async insert a new row and return RequestContext for getting auto-increment ID.
     *
     * @param string|null $sequence
     *
     * @return RequestContext
     *
     * @throws OTSClientException
     */
    public function asyncInsertGetId(array $values, $sequence = null)
    {
        $primaryKey = [];
        $attributeColumns = [];
        $autoIncrementKey = $sequence;

        foreach ($this->primaryKeyColumns as $key => $type) {
            if ($type === PrimaryKeyTypeConst::CONST_PK_AUTO_INCR) {
                $primaryKey[] = [$key, null, $type];
                $autoIncrementKey = $autoIncrementKey ?? $key;
            } elseif (isset($values[$key])) {
                $primaryKey[] = [$key, $values[$key]];
            }
        }

        foreach ($values as $key => $value) {
            if (!isset($this->primaryKeyColumns[$key])) {
                $attributeColumns[] = [$key, $value];
            }
        }

        $request = [
            'table_name' => $this->from,
            'condition' => $this->rowExistence,
            'primary_key' => $primaryKey,
            'attribute_columns' => $attributeColumns,
            'return_content' => [
                'return_type' => 1, // RETURN_PK
            ],
        ];

        return $this->connection->asyncDoHandle('PutRow', $request);
    }

    /**
     * Update a row.
     *
     * @return int|RequestContext
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function update(array $values)
    {
        if (empty($this->primaryKeys)) {
            throw new OTSClientException('Primary key is required for update.');
        }

        $updateColumns = [];
        foreach ($values as $key => $value) {
            if ($value === null) {
                $updateColumns['DELETE_ALL'][] = $key;
            } else {
                $updateColumns['PUT'][] = [$key, $value];
            }
        }

        $request = [
            'table_name' => $this->from,
            'condition' => $this->rowExistence,
            'primary_key' => $this->formatPrimaryKey($this->primaryKeys),
            'update_of_attribute_columns' => $updateColumns,
        ];

        if ($this->async) {
            return $this->connection->asyncDoHandle('UpdateRow', $request);
        }

        $response = $this->connection->updateRow($request);

        return is_array($response) ? 1 : 0;
    }

    /**
     * Async update a row.
     *
     * @return RequestContext
     *
     * @throws OTSClientException
     */
    public function asyncUpdate(array $values)
    {
        return $this->async()->update($values);
    }

    /**
     * Delete a row.
     *
     * @param mixed $id
     *
     * @return int|RequestContext
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function delete($id = null)
    {
        $primaryKey = $id ? (is_array($id) ? $id : ['id' => $id]) : $this->primaryKeys;

        if (empty($primaryKey)) {
            throw new OTSClientException('Primary key is required for delete.');
        }

        $request = [
            'table_name' => $this->from,
            'condition' => $this->rowExistence,
            'primary_key' => $this->formatPrimaryKey($primaryKey),
        ];

        if ($this->async) {
            return $this->connection->asyncDoHandle('DeleteRow', $request);
        }

        $response = $this->connection->deleteRow($request);

        return is_array($response) ? 1 : 0;
    }

    /**
     * Async delete a row.
     *
     * @param mixed $id
     *
     * @return RequestContext
     *
     * @throws OTSClientException
     */
    public function asyncDelete($id = null)
    {
        return $this->async()->delete($id);
    }

    /**
     * Get the first result.
     *
     * @param array|string $columns
     *
     * @return array|null
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function first($columns = ['*'])
    {
        $this->limit = 1;

        if ($columns !== ['*']) {
            $this->columns = is_array($columns) ? $columns : func_get_args();
        }

        // If we have primary keys, use findByPrimaryKey
        if (!empty($this->primaryKeys)) {
            return $this->findByPrimaryKey($this->primaryKeys);
        }

        // If we have search index, use search
        if (!empty($this->searchIndex)) {
            $results = $this->searchQuery();

            return $results->first();
        }

        // Use range query
        if ($this->startPrimaryKey !== null || $this->endPrimaryKey !== null) {
            $results = $this->getRange();

            return $results->first();
        }

        return null;
    }

    /**
     * Get results.
     *
     * @param array|string $columns
     *
     * @return Collection
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function get($columns = ['*'])
    {
        if ($columns !== ['*']) {
            $this->columns = is_array($columns) ? $columns : func_get_args();
        }

        // If we have search index, use search
        if (!empty($this->searchIndex)) {
            return $this->searchQuery();
        }

        // Use range query
        if ($this->startPrimaryKey !== null || $this->endPrimaryKey !== null) {
            return $this->getRange();
        }

        return new Collection();
    }

    /**
     * Format primary key for OTS request.
     *
     * @return array
     */
    protected function formatPrimaryKey(array $primaryKey)
    {
        $formatted = [];
        foreach ($primaryKey as $key => $value) {
            if (is_array($value)) {
                $formatted[] = $value;
            } else {
                $formatted[] = [$key, $value];
            }
        }

        return $formatted;
    }

    /**
     * Parse a row response from OTS.
     *
     * @return array|null
     */
    protected function parseRow(array $response)
    {
        if (empty($response['row']) && empty($response['primary_key'])) {
            return null;
        }

        $row = [];

        // Parse from row format
        if (isset($response['row'])) {
            $rowData = $response['row'];
            foreach ($rowData['primary_key'] ?? [] as $pk) {
                $row[$pk[0]] = $pk[1];
            }
            foreach ($rowData['attribute_columns'] ?? [] as $attr) {
                $row[$attr[0]] = $attr[1];
            }
        } else {
            // Parse from direct format
            foreach ($response['primary_key'] ?? [] as $pk) {
                $row[$pk[0]] = $pk[1];
            }
            foreach ($response['attribute_columns'] ?? [] as $attr) {
                $row[$attr[0]] = $attr[1];
            }
        }

        return empty($row) ? null : $row;
    }

    /**
     * Build search query from search conditions.
     *
     * @return array
     */
    protected function buildSearchQuery()
    {
        // Check if we have explicit bool query conditions
        $hasBoolConditions = !empty($this->searchMustQueries)
            || !empty($this->searchShouldQueries)
            || !empty($this->searchMustNotQueries)
            || !empty($this->searchFilterQueries);

        if ($hasBoolConditions) {
            return $this->buildBoolQuery();
        }

        if (empty($this->searchQueries)) {
            return [
                'query_type' => QueryTypeConst::MATCH_ALL_QUERY,
            ];
        }

        if (count($this->searchQueries) === 1) {
            return $this->buildSingleSearchQuery($this->searchQueries[0]);
        }

        // Multiple conditions - use bool query with must
        $mustQueries = [];
        foreach ($this->searchQueries as $query) {
            $mustQueries[] = $this->buildSingleSearchQuery($query);
        }

        return [
            'query_type' => QueryTypeConst::BOOL_QUERY,
            'query' => [
                'must_queries' => $mustQueries,
            ],
        ];
    }

    /**
     * Build a complete bool query with all conditions.
     *
     * @return array
     */
    protected function buildBoolQuery()
    {
        $boolQuery = [];

        // Add must queries (AND logic)
        if (!empty($this->searchMustQueries)) {
            $boolQuery['must_queries'] = $this->searchMustQueries;
        }

        // Add should queries (OR logic)
        if (!empty($this->searchShouldQueries)) {
            $boolQuery['should_queries'] = $this->searchShouldQueries;
            if ($this->minimumShouldMatch !== null) {
                $boolQuery['minimum_should_match'] = $this->minimumShouldMatch;
            }
        }

        // Add must_not queries (NOT logic)
        if (!empty($this->searchMustNotQueries)) {
            $boolQuery['must_not_queries'] = $this->searchMustNotQueries;
        }

        // Add filter queries (no scoring)
        if (!empty($this->searchFilterQueries)) {
            $boolQuery['filter_queries'] = $this->searchFilterQueries;
        }

        // Also add simple searchQueries as must conditions
        if (!empty($this->searchQueries)) {
            $mustQueries = $boolQuery['must_queries'] ?? [];
            foreach ($this->searchQueries as $query) {
                $mustQueries[] = $this->buildSingleSearchQuery($query);
            }
            $boolQuery['must_queries'] = $mustQueries;
        }

        return [
            'query_type' => QueryTypeConst::BOOL_QUERY,
            'query' => $boolQuery,
        ];
    }

    /**
     * Build a single search query condition.
     *
     * @return array
     */
    protected function buildSingleSearchQuery(array $condition)
    {
        $type = $condition['type'] ?? null;

        if ($type === QueryTypeConst::MATCH_ALL_QUERY) {
            return ['query_type' => QueryTypeConst::MATCH_ALL_QUERY];
        }

        if ($type === QueryTypeConst::TERM_QUERY) {
            return [
                'query_type' => QueryTypeConst::TERM_QUERY,
                'query' => [
                    'field_name' => $condition['column'],
                    'term' => $condition['value'],
                ],
            ];
        }

        if ($type === QueryTypeConst::TERMS_QUERY) {
            return [
                'query_type' => QueryTypeConst::TERMS_QUERY,
                'query' => [
                    'field_name' => $condition['column'],
                    'terms' => $condition['values'],
                ],
            ];
        }

        if ($type === QueryTypeConst::MATCH_QUERY) {
            return [
                'query_type' => QueryTypeConst::MATCH_QUERY,
                'query' => [
                    'field_name' => $condition['column'],
                    'text' => $condition['value'],
                ],
            ];
        }

        if ($type === QueryTypeConst::MATCH_PHRASE_QUERY) {
            return [
                'query_type' => QueryTypeConst::MATCH_PHRASE_QUERY,
                'query' => [
                    'field_name' => $condition['column'],
                    'text' => $condition['value'],
                ],
            ];
        }

        if ($type === QueryTypeConst::PREFIX_QUERY) {
            return [
                'query_type' => QueryTypeConst::PREFIX_QUERY,
                'query' => [
                    'field_name' => $condition['column'],
                    'prefix' => $condition['value'],
                ],
            ];
        }

        if ($type === QueryTypeConst::RANGE_QUERY) {
            $query = ['field_name' => $condition['column']];
            if ($condition['from'] !== null) {
                $query['range_from'] = $condition['from'];
                $query['include_lower'] = $condition['include_from'];
            }
            if ($condition['to'] !== null) {
                $query['range_to'] = $condition['to'];
                $query['include_upper'] = $condition['include_to'];
            }

            return [
                'query_type' => QueryTypeConst::RANGE_QUERY,
                'query' => $query,
            ];
        }

        if ($type === QueryTypeConst::WILDCARD_QUERY) {
            return [
                'query_type' => QueryTypeConst::WILDCARD_QUERY,
                'query' => [
                    'field_name' => $condition['column'],
                    'value' => $condition['value'],
                ],
            ];
        }

        if ($type === QueryTypeConst::EXISTS_QUERY) {
            return [
                'query_type' => QueryTypeConst::EXISTS_QUERY,
                'query' => [
                    'field_name' => $condition['column'],
                ],
            ];
        }

        if ($type === QueryTypeConst::NESTED_QUERY) {
            $nestedQuery = [
                'path' => $condition['path'],
                'query' => $condition['query'],
            ];
            if (isset($condition['score_mode'])) {
                $nestedQuery['score_mode'] = $condition['score_mode'];
            }

            return [
                'query_type' => QueryTypeConst::NESTED_QUERY,
                'query' => $nestedQuery,
            ];
        }

        if ($type === QueryTypeConst::GEO_DISTANCE_QUERY) {
            return [
                'query_type' => QueryTypeConst::GEO_DISTANCE_QUERY,
                'query' => [
                    'field_name' => $condition['column'],
                    'center_point' => $condition['center_point'],
                    'distance' => $condition['distance'],
                ],
            ];
        }

        if ($type === QueryTypeConst::GEO_BOUNDING_BOX_QUERY) {
            return [
                'query_type' => QueryTypeConst::GEO_BOUNDING_BOX_QUERY,
                'query' => [
                    'field_name' => $condition['column'],
                    'top_left' => $condition['top_left'],
                    'bottom_right' => $condition['bottom_right'],
                ],
            ];
        }

        if ($type === QueryTypeConst::GEO_POLYGON_QUERY) {
            return [
                'query_type' => QueryTypeConst::GEO_POLYGON_QUERY,
                'query' => [
                    'field_name' => $condition['column'],
                    'points' => $condition['points'],
                ],
            ];
        }

        // Default: convert operator-based condition to appropriate query type
        $operator = $condition['operator'] ?? '=';
        $column = $condition['column'];
        $value = $condition['value'];

        if ($operator === '=' || $operator === '==') {
            return [
                'query_type' => QueryTypeConst::TERM_QUERY,
                'query' => [
                    'field_name' => $column,
                    'term' => $value,
                ],
            ];
        }

        if ($operator === 'like') {
            return [
                'query_type' => QueryTypeConst::WILDCARD_QUERY,
                'query' => [
                    'field_name' => $column,
                    'value' => str_replace(['%', '_'], ['*', '?'], $value),
                ],
            ];
        }

        return [
            'query_type' => QueryTypeConst::TERM_QUERY,
            'query' => [
                'field_name' => $column,
                'term' => $value,
            ],
        ];
    }

    /**
     * Compile wheres to SQL string.
     *
     * @return string
     */
    protected function compileWheres()
    {
        $parts = [];
        foreach ($this->wheres as $where) {
            if ($where['type'] === 'Basic') {
                $value = is_string($where['value']) ? "'{$where['value']}'" : $where['value'];
                $parts[] = "{$where['column']} {$where['operator']} {$value}";
            }
        }

        return implode(' AND ', $parts);
    }
}
