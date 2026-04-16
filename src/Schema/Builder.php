<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/27
 * Time: 16:44.
 */

namespace HughCube\Laravel\OTS\Schema;

use Aliyun\OTS\Consts\PrimaryKeyTypeConst as PKType;
use Aliyun\OTS\OTSClientException;
use Aliyun\OTS\OTSServerException;
use Closure;
use HughCube\Laravel\OTS\Connection;
use HughCube\Laravel\OTS\Exceptions\DropTableException;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Collection;
use LogicException;

/**
 * @method Connection getConnection()
 *
 * @property Grammar $grammar
 */
class Builder extends \Illuminate\Database\Schema\Builder
{
    /**
     * @inheritdoc
     *
     * @throws OTSServerException
     * @throws OTSClientException
     */
    public function hasTable($table): bool
    {
        try {
            $response = $this->getConnection()->describeTable(['table_name' => $table]);

            return isset($response['table_meta']) && is_array($response['table_meta']);
        } catch (OTSServerException $exception) {
            if (404 === $exception->getHttpStatus()) {
                return false;
            }

            throw $exception;
        }
    }

    /**
     * @inheritdoc
     */
    public function hasColumn($table, $column): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function hasColumns($table, array $columns): bool
    {
        return true;
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     */
    public function create(
        $table,
        Closure $callback,
        array $throughput = ['capacity_unit' => ['read' => 0, 'write' => 0]],
        array $options = ['time_to_live' => -1, 'max_versions' => 1, 'deviation_cell_version_in_sec' => 86400]
    ) {
        $blueprint = $this->createBlueprint($table);

        $callback($blueprint);

        $tableMeta = [
            'table_name'         => $table,
            'primary_key_schema' => Collection::make($blueprint->getColumns())
                ->map(function (ColumnDefinition $column) {
                    if (!$column->get('primary')) {
                        throw new OTSClientException('Only primary keys can be defined!');
                    }

                    if (!$column->get('autoIncrement')) {
                        return [$column->get('name'), $this->grammar->getType($column)];
                    }

                    return [$column->get('name'), $this->grammar->getType($column), PKType::CONST_PK_AUTO_INCR];
                })->filter()->values()->toArray(),
        ];

        // Add defined columns if any
        $definedColumns = $blueprint->getDefinedColumns();
        if (!empty($definedColumns)) {
            $tableMeta['defined_column'] = $definedColumns;
        }

        $request = [
            'table_meta' => $tableMeta,
            'reserved_throughput' => $throughput,
            'table_options'       => $options,
        ];

        $response = $this->getConnection()->createTable($request);
        if (!is_array($response)) {
            throw new OTSClientException('Failed to create a table!');
        }
    }

    /**
     * @inheritdoc
     *
     * @return Blueprint
     */
    protected function createBlueprint($table, ?Closure $callback = null)
    {
        return new Blueprint($this->getConnection(), $table, $callback);
    }

    /**
     * @inheritdoc
     *
     * @throws DropTableException
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function drop($table)
    {
        // Delete all search indexes before dropping the table
        $this->dropAllSearchIndexes($table);

        $response = $this->getConnection()->deleteTable(['table_name' => $table]);
        if (!is_array($response)) {
            throw new DropTableException('Data sheet injury failed!');
        }
    }

    /**
     * Drop all search indexes for a table.
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function dropAllSearchIndexes(string $table): void
    {
        try {
            $indexes = $this->getConnection()->listSearchIndex(['table_name' => $table]);
            if (!empty($indexes)) {
                foreach ($indexes as $index) {
                    if (isset($index['index_name'])) {
                        $this->getConnection()->deleteSearchIndex([
                            'table_name' => $table,
                            'index_name' => $index['index_name'],
                        ]);
                    }
                }
            }
        } catch (OTSServerException $e) {
            // Ignore errors if table doesn't exist or no indexes
            if (404 !== $e->getHttpStatus()) {
                throw $e;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function dropColumns($table, $columns)
    {
    }

    /**
     * @inheritDoc
     *
     * @throws DropTableException
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function dropAllTables($skipTables = [])
    {
        foreach ($this->getAllTables() as $table) {
            if (!in_array($table, $skipTables)) {
                $this->drop($table);
            }
        }
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     *
     * @return array
     */
    public function getAllTables(): array
    {
        return $this->getConnection()->listTable([]);
    }

    /**
     * @inheritDoc
     */
    public function rename($from, $to)
    {
        throw new LogicException('This database driver does not support rename table.');
    }

    /**
     * Create a secondary index (global or local).
     *
     * @param string $table The table name
     * @param string $indexName The index name
     * @param array $primaryKey The primary key columns for the index
     * @param array $definedColumns The defined columns to include
     * @param string $indexType The index type (GLOBAL_INDEX or LOCAL_INDEX)
     * @param string $updateMode The update mode (ASYNC_INDEX or SYNC_INDEX)
     * @param bool $includeBaseData Whether to include existing data
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function createSecondaryIndex(
        string $table,
        string $indexName,
        array $primaryKey,
        array $definedColumns = [],
        string $indexType = 'GLOBAL_INDEX',
        string $updateMode = 'ASYNC_INDEX',
        bool $includeBaseData = true
    ): void {
        $request = [
            'table_name' => $table,
            'index_meta' => [
                'name' => $indexName,
                'primary_key' => $primaryKey,
                'defined_column' => $definedColumns,
                'index_type' => $indexType,
                'index_update_mode' => $updateMode,
            ],
            'include_base_data' => $includeBaseData,
        ];

        $this->getConnection()->createIndex($request);
    }

    /**
     * Create a global secondary index.
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function createGlobalIndex(
        string $table,
        string $indexName,
        array $primaryKey,
        array $definedColumns = [],
        bool $includeBaseData = true
    ): void {
        $this->createSecondaryIndex(
            $table,
            $indexName,
            $primaryKey,
            $definedColumns,
            'GLOBAL_INDEX',
            'ASYNC_INDEX',
            $includeBaseData
        );
    }

    /**
     * Create a local secondary index.
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function createLocalIndex(
        string $table,
        string $indexName,
        array $primaryKey,
        array $definedColumns = [],
        bool $includeBaseData = true
    ): void {
        $this->createSecondaryIndex(
            $table,
            $indexName,
            $primaryKey,
            $definedColumns,
            'LOCAL_INDEX',
            'SYNC_INDEX',
            $includeBaseData
        );
    }

    /**
     * Drop a secondary index.
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function dropSecondaryIndex(string $table, string $indexName): void
    {
        $this->getConnection()->dropIndex([
            'table_name' => $table,
            'index_name' => $indexName,
        ]);
    }

    /**
     * Create a search index.
     *
     * @param string $table The table name
     * @param string $indexName The index name
     * @param array $fieldSchemas The field schemas
     * @param array $indexSetting Optional index settings
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function createSearchIndex(
        string $table,
        string $indexName,
        array $fieldSchemas,
        array $indexSetting = []
    ): void {
        $request = [
            'table_name' => $table,
            'index_name' => $indexName,
            'schema' => [
                'field_schemas' => $fieldSchemas,
            ],
        ];

        if (!empty($indexSetting)) {
            $request['schema']['index_setting'] = $indexSetting;
        }

        $this->getConnection()->createSearchIndex($request);
    }

    /**
     * Drop a search index.
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function dropSearchIndex(string $table, string $indexName): void
    {
        $this->getConnection()->deleteSearchIndex([
            'table_name' => $table,
            'index_name' => $indexName,
        ]);
    }

    /**
     * Update a search index.
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function updateSearchIndex(string $table, string $indexName, array $schema): void
    {
        $this->getConnection()->updateSearchIndex([
            'table_name' => $table,
            'index_name' => $indexName,
            'schema' => $schema,
        ]);
    }

    /**
     * Describe a search index.
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function describeSearchIndex(string $table, string $indexName): array
    {
        return $this->getConnection()->describeSearchIndex([
            'table_name' => $table,
            'index_name' => $indexName,
        ]);
    }

    /**
     * List all search indexes for a table.
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function listSearchIndexes(string $table): array
    {
        return $this->getConnection()->listSearchIndex([
            'table_name' => $table,
        ]);
    }

    /**
     * Check if a table has any search index.
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function hasSearchIndex(string $table, ?string $indexName = null): bool
    {
        try {
            $indexes = $this->listSearchIndexes($table);

            if (empty($indexes)) {
                return false;
            }

            // If no specific index name, just check if any index exists
            if ($indexName === null) {
                return true;
            }

            // Check for specific index name
            foreach ($indexes as $index) {
                if (isset($index['index_name']) && $index['index_name'] === $indexName) {
                    return true;
                }
            }

            return false;
        } catch (OTSServerException $e) {
            if (404 === $e->getHttpStatus()) {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Check if a table has a secondary index.
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function hasSecondaryIndex(string $table, ?string $indexName = null): bool
    {
        try {
            $response = $this->getConnection()->describeTable(['table_name' => $table]);
            $indexes = $response['index_metas'] ?? [];

            if (empty($indexes)) {
                return false;
            }

            if ($indexName === null) {
                return true;
            }

            foreach ($indexes as $index) {
                if (isset($index['name']) && $index['name'] === $indexName) {
                    return true;
                }
            }

            return false;
        } catch (OTSServerException $e) {
            if (404 === $e->getHttpStatus()) {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Compute split points by size for a table.
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function computeSplitPointsBySize(string $table, int $splitSize): array
    {
        return $this->getConnection()->computeSplitPointsBySize([
            'table_name' => $table,
            'split_size' => $splitSize,
        ]);
    }
}
