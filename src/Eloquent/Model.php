<?php

namespace HughCube\Laravel\OTS\Eloquent;

use Aliyun\OTS\Consts\PrimaryKeyTypeConst;
use HughCube\Laravel\OTS\Connection;
use HughCube\Laravel\OTS\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as BaseModel;

/**
 * @phpstan-consistent-constructor
 */
abstract class Model extends BaseModel
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'ots';

    /**
     * The primary key columns definition.
     * Format: ['column_name' => PrimaryKeyTypeConst, ...]
     *
     * @var array
     */
    protected $primaryKeyColumns = [];

    /**
     * The search index name for this model.
     *
     * @var string|null
     */
    protected $searchIndex = null;

    /**
     * Indicates if the model should be timestamped.
     * OTS doesn't have built-in timestamp support like MySQL.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Get the primary key columns definition.
     *
     * @return array
     */
    public function getPrimaryKeyColumns()
    {
        return $this->primaryKeyColumns;
    }

    /**
     * Get the search index name.
     *
     * @return string|null
     */
    public function getSearchIndex()
    {
        return $this->searchIndex;
    }

    /**
     * Set the search index name.
     *
     * @param string $indexName
     *
     * @return $this
     */
    public function setSearchIndex($indexName)
    {
        $this->searchIndex = $indexName;

        return $this;
    }

    /**
     * Get the primary key values as array.
     *
     * @return array
     */
    public function getPrimaryKeyValues()
    {
        $values = [];
        foreach (array_keys($this->primaryKeyColumns) as $column) {
            $values[$column] = $this->getAttribute($column);
        }

        return $values;
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param QueryBuilder $query
     *
     * @return Builder
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return QueryBuilder
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        $builder = new QueryBuilder(
            $connection,
            $connection->getQueryGrammar(),
            $connection->getPostProcessor()
        );

        // Set primary key columns
        $builder->primaryKeyColumns($this->primaryKeyColumns);

        // Set search index if defined
        if ($this->searchIndex) {
            $builder->useSearchIndex($this->searchIndex);
        }

        return $builder;
    }

    /**
     * Get the database connection for the model.
     *
     * @return Connection
     */
    public function getConnection()
    {
        $connection = static::resolveConnection($this->getConnectionName());

        if (!$connection instanceof Connection) {
            throw new \LogicException(sprintf(
                'OTS Eloquent model requires an OTS connection, got %s.',
                get_class($connection)
            ));
        }

        return $connection;
    }

    /**
     * Perform a model insert operation.
     *
     * @param EloquentBuilder $query
     *
     * @return bool
     */
    protected function performInsert(EloquentBuilder $query)
    {
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        // Set timestamps if needed
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        $attributes = $this->getAttributesForInsert();

        // Build primary key
        $primaryKey = [];
        $attributeColumns = [];

        foreach ($attributes as $key => $value) {
            if (isset($this->primaryKeyColumns[$key])) {
                $pkType = $this->primaryKeyColumns[$key];
                if ($pkType === PrimaryKeyTypeConst::CONST_PK_AUTO_INCR) {
                    $primaryKey[] = [$key, null, $pkType];
                } else {
                    $primaryKey[] = [$key, $value];
                }
            } else {
                $attributeColumns[] = [$key, $value];
            }
        }

        $request = [
            'table_name' => $this->getTable(),
            'condition' => 'IGNORE',
            'primary_key' => $primaryKey,
            'attribute_columns' => $attributeColumns,
            'return_content' => [
                'return_type' => 1, // RETURN_PK
            ],
        ];

        $response = $this->getConnection()->putRow($request);

        // Set auto-increment ID if applicable
        if (isset($response['primary_key'])) {
            foreach ($response['primary_key'] as $pk) {
                if (isset($this->primaryKeyColumns[$pk[0]]) &&
                    $this->primaryKeyColumns[$pk[0]] === PrimaryKeyTypeConst::CONST_PK_AUTO_INCR) {
                    $this->setAttribute($pk[0], $pk[1]);
                }
            }
        }

        $this->exists = true;
        $this->wasRecentlyCreated = true;

        $this->fireModelEvent('created', false);

        return true;
    }

    /**
     * Perform a model update operation.
     *
     * @param EloquentBuilder $query
     *
     * @return bool
     */
    protected function performUpdate(EloquentBuilder $query)
    {
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        // Set timestamps if needed
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        $dirty = $this->getDirty();

        if (count($dirty) > 0) {
            // Build update columns
            $updateColumns = ['PUT' => []];
            foreach ($dirty as $key => $value) {
                if (!isset($this->primaryKeyColumns[$key])) {
                    if ($value === null) {
                        $updateColumns['DELETE_ALL'][] = $key;
                    } else {
                        $updateColumns['PUT'][] = [$key, $value];
                    }
                }
            }

            // Build primary key
            $primaryKey = [];
            foreach ($this->primaryKeyColumns as $column => $type) {
                $primaryKey[] = [$column, $this->getAttribute($column)];
            }

            $request = [
                'table_name' => $this->getTable(),
                'condition' => 'IGNORE',
                'primary_key' => $primaryKey,
                'update_of_attribute_columns' => $updateColumns,
            ];

            $this->getConnection()->updateRow($request);

            $this->syncChanges();
        }

        $this->fireModelEvent('updated', false);

        return true;
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function performDeleteOnModel()
    {
        // Build primary key
        $primaryKey = [];
        foreach ($this->primaryKeyColumns as $column => $type) {
            $primaryKey[] = [$column, $this->getAttribute($column)];
        }

        $request = [
            'table_name' => $this->getTable(),
            'condition' => 'IGNORE',
            'primary_key' => $primaryKey,
        ];

        $this->getConnection()->deleteRow($request);

        $this->exists = false;
    }

    /**
     * Find a model by its primary key.
     *
     * @param array|mixed $id
     * @param array $columns
     *
     * @return static|null
     */
    public static function find($id, $columns = ['*'])
    {
        $instance = new static();

        $primaryKey = is_array($id) ? $id : [$instance->getKeyName() => $id];

        $request = [
            'table_name' => $instance->getTable(),
            'primary_key' => array_map(function ($k, $v) {
                return [$k, $v];
            }, array_keys($primaryKey), array_values($primaryKey)),
            'max_versions' => 1,
        ];

        if ($columns !== ['*']) {
            $request['columns_to_get'] = $columns;
        }

        $response = $instance->getConnection()->getRow($request);

        if (empty($response['row']) && empty($response['primary_key'])) {
            return null;
        }

        $attributes = [];

        // Parse primary key
        foreach ($response['primary_key'] ?? $response['row']['primary_key'] ?? [] as $pk) {
            $attributes[$pk[0]] = $pk[1];
        }

        // Parse attribute columns
        foreach ($response['attribute_columns'] ?? $response['row']['attribute_columns'] ?? [] as $attr) {
            $attributes[$attr[0]] = $attr[1];
        }

        $model = $instance->newInstance([], true);
        $model->setRawAttributes($attributes, true);

        return $model;
    }

    /**
     * Find multiple models by their primary keys.
     *
     * @param array $ids Array of primary key arrays
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findMany(array $ids, $columns = ['*'])
    {
        $instance = new static();

        $primaryKeys = array_map(function ($id) use ($instance) {
            if (!is_array($id)) {
                $id = [$instance->getKeyName() => $id];
            }

            return array_map(function ($k, $v) {
                return [$k, $v];
            }, array_keys($id), array_values($id));
        }, $ids);

        $request = [
            'tables' => [
                [
                    'table_name' => $instance->getTable(),
                    'primary_keys' => $primaryKeys,
                    'max_versions' => 1,
                ],
            ],
        ];

        if ($columns !== ['*']) {
            $request['tables'][0]['columns_to_get'] = $columns;
        }

        $response = $instance->getConnection()->batchGetRow($request);

        $models = [];
        foreach ($response['tables'][0]['rows'] ?? [] as $row) {
            if (!empty($row['is_ok'])) {
                $attributes = [];

                foreach ($row['primary_key'] ?? [] as $pk) {
                    $attributes[$pk[0]] = $pk[1];
                }

                foreach ($row['attribute_columns'] ?? [] as $attr) {
                    $attributes[$attr[0]] = $attr[1];
                }

                if (!empty($attributes)) {
                    $model = $instance->newInstance([], true);
                    $model->setRawAttributes($attributes, true);
                    $models[] = $model;
                }
            }
        }

        return $instance->newCollection($models);
    }

    /**
     * Get the attributes that should be inserted.
     *
     * @return array
     */
    protected function getAttributesForInsert()
    {
        return $this->getAttributes();
    }
}
