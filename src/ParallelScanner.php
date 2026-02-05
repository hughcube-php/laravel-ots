<?php

namespace HughCube\Laravel\OTS;

use Aliyun\OTS\Consts\ColumnReturnTypeConst;
use Aliyun\OTS\Consts\QueryTypeConst;
use Aliyun\OTS\OTSClientException;
use Aliyun\OTS\OTSServerException;
use Generator;
use Illuminate\Support\Collection;

class ParallelScanner
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var string
     */
    protected $indexName;

    /**
     * @var string|null
     */
    protected $sessionId = null;

    /**
     * @var int
     */
    protected $maxParallel = 1;

    /**
     * @var array
     */
    protected $query = [];

    /**
     * @var int
     */
    protected $limit = 100;

    /**
     * @var int
     */
    protected $aliveTime = 30;

    /**
     * @var array
     */
    protected $columnsToGet = [];

    /**
     * @var string
     */
    protected $returnType = ColumnReturnTypeConst::RETURN_ALL_FROM_INDEX;

    /**
     * @param Connection $connection
     * @param string $tableName
     * @param string $indexName
     */
    public function __construct(Connection $connection, $tableName, $indexName)
    {
        $this->connection = $connection;
        $this->tableName = $tableName;
        $this->indexName = $indexName;
        $this->query = [
            'query_type' => QueryTypeConst::MATCH_ALL_QUERY,
        ];
    }

    /**
     * Set the query for scanning.
     *
     * @param array $query
     *
     * @return $this
     */
    public function query(array $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Set match all query.
     *
     * @return $this
     */
    public function matchAll()
    {
        $this->query = [
            'query_type' => QueryTypeConst::MATCH_ALL_QUERY,
        ];

        return $this;
    }

    /**
     * Set the limit per scan request.
     *
     * @param int $limit
     *
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Set the alive time for the scan session (seconds).
     *
     * @param int $seconds
     *
     * @return $this
     */
    public function aliveTime($seconds)
    {
        $this->aliveTime = $seconds;

        return $this;
    }

    /**
     * Set the columns to return.
     *
     * @param array $columns
     *
     * @return $this
     */
    public function columns(array $columns)
    {
        $this->columnsToGet = $columns;
        $this->returnType = ColumnReturnTypeConst::RETURN_SPECIFIED;

        return $this;
    }

    /**
     * Return all columns from index.
     *
     * @return $this
     */
    public function returnAllFromIndex()
    {
        $this->returnType = ColumnReturnTypeConst::RETURN_ALL_FROM_INDEX;
        $this->columnsToGet = [];

        return $this;
    }

    /**
     * Compute splits and get session info.
     *
     * @return array
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function computeSplits()
    {
        $response = $this->connection->computeSplits([
            'table_name' => $this->tableName,
            'search_index_splits_options' => [
                'index_name' => $this->indexName,
            ],
        ]);

        $this->sessionId = $response['session_id'];
        $this->maxParallel = $response['splits_size'] ?? 1;

        return [
            'session_id' => $this->sessionId,
            'max_parallel' => $this->maxParallel,
        ];
    }

    /**
     * Get session ID (compute splits if needed).
     *
     * @return string
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function getSessionId()
    {
        if ($this->sessionId === null) {
            $this->computeSplits();
        }

        return $this->sessionId;
    }

    /**
     * Get max parallel count.
     *
     * @return int
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function getMaxParallel()
    {
        if ($this->sessionId === null) {
            $this->computeSplits();
        }

        return $this->maxParallel;
    }

    /**
     * Scan a specific parallel partition.
     *
     * @param int $parallelId The parallel partition ID (0-based)
     *
     * @return Generator Yields rows from the scan
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function scanPartition($parallelId)
    {
        $sessionId = $this->getSessionId();
        $token = null;

        do {
            $request = [
                'table_name' => $this->tableName,
                'index_name' => $this->indexName,
                'session_id' => $sessionId,
                'scan_query' => [
                    'query' => $this->query,
                    'limit' => $this->limit,
                    'alive_time' => $this->aliveTime,
                    'token' => $token,
                    'current_parallel_id' => $parallelId,
                    'max_parallel' => $this->maxParallel,
                ],
                'columns_to_get' => $this->buildColumnsToGet(),
            ];

            $response = $this->connection->parallelScan($request);

            foreach ($response['rows'] ?? [] as $row) {
                yield $this->parseRow($row);
            }

            $token = $response['next_token'] ?? null;
        } while ($token !== null);
    }

    /**
     * Scan all partitions sequentially.
     *
     * @return Generator Yields rows from all partitions
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function scan()
    {
        $maxParallel = $this->getMaxParallel();

        for ($i = 0; $i < $maxParallel; $i++) {
            yield from $this->scanPartition($i);
        }
    }

    /**
     * Scan and collect all results.
     *
     * @return Collection
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function get()
    {
        $rows = [];
        foreach ($this->scan() as $row) {
            $rows[] = $row;
        }

        return new Collection($rows);
    }

    /**
     * Scan a partition and collect results.
     *
     * @param int $parallelId
     *
     * @return Collection
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function getPartition($parallelId)
    {
        $rows = [];
        foreach ($this->scanPartition($parallelId) as $row) {
            $rows[] = $row;
        }

        return new Collection($rows);
    }

    /**
     * Get partition scan requests for parallel execution.
     *
     * @return array Array of closures that return generators
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function getPartitionScanners()
    {
        $maxParallel = $this->getMaxParallel();
        $scanners = [];

        for ($i = 0; $i < $maxParallel; $i++) {
            $parallelId = $i;
            $that = $this;
            $scanners[] = function () use ($that, $parallelId) {
                return $that->scanPartition($parallelId);
            };
        }

        return $scanners;
    }

    /**
     * Count total rows (scans all data).
     *
     * @return int
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function count()
    {
        $count = 0;
        foreach ($this->scan() as $row) {
            $count++;
        }

        return $count;
    }

    /**
     * Build columns_to_get request parameter.
     *
     * @return array
     */
    protected function buildColumnsToGet()
    {
        $result = [
            'return_type' => $this->returnType,
        ];

        if (!empty($this->columnsToGet)) {
            $result['return_names'] = $this->columnsToGet;
        }

        return $result;
    }

    /**
     * Parse a row from the scan response.
     *
     * @param array $row
     *
     * @return array
     */
    protected function parseRow(array $row)
    {
        $result = [];

        foreach ($row['primary_key'] ?? [] as $pk) {
            $result[$pk[0]] = $pk[1];
        }

        foreach ($row['attribute_columns'] ?? [] as $attr) {
            $result[$attr[0]] = $attr[1];
        }

        return $result;
    }
}
