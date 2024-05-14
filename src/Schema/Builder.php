<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/27
 * Time: 16:44
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
 * @method  Connection getConnection()
 *
 * @property Grammar $grammar
 */
class Builder extends \Illuminate\Database\Schema\Builder
{
    /**
     * @inheritdoc
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

        $request = [
            'table_meta' => [
                'table_name' => $table,
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
            ],
            'reserved_throughput' => $throughput,
            'table_options' => $options
        ];

        $response = $this->getConnection()->createTable($request);
        if (!is_array($response)) {
            throw new OTSClientException('Failed to create a table!');
        }
    }

    /**
     * @inheritdoc
     */
    protected function createBlueprint($table, Closure $callback = null)
    {
        $prefix = $this->getConnection()->getConfig('prefix_indexes')
            ? $this->getConnection()->getConfig('prefix')
            : '';

        return new Blueprint($this->getConnection(), $table, $callback, $prefix);
    }

    /**
     * @inheritdoc
     * @throws DropTableException
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function drop($table)
    {
        $response = $this->getConnection()->deleteTable(['table_name' => $table]);
        if (!is_array($response)) {
            throw new DropTableException('Data sheet injury failed!');
        }
    }

    /**
     * @inheritDoc
     */
    public function dropColumns($table, $columns)
    {
        return;
    }

    /**
     * @inheritDoc
     * @throws DropTableException
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function dropAllTables($skipTables = [])
    {
        foreach ($this->getAllTables() as $table) {
            if(!in_array($table, $skipTables)){
                $this->drop($table);
            }
        }
    }

    /**
     * @return array
     * @throws OTSServerException
     * @throws OTSClientException
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
}
