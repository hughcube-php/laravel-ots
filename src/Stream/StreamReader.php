<?php

namespace HughCube\Laravel\OTS\Stream;

use Aliyun\OTS\OTSClientException;
use Aliyun\OTS\OTSServerException;
use Generator;
use HughCube\Laravel\OTS\Connection;
use Illuminate\Support\Collection;

class StreamReader
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
     * @var string|null
     */
    protected $streamId = null;

    /**
     * @var int
     */
    protected $limit = 100;

    /**
     * @param Connection $connection
     * @param string $tableName
     */
    public function __construct(Connection $connection, $tableName)
    {
        $this->connection = $connection;
        $this->tableName = $tableName;
    }

    /**
     * Set the stream ID directly.
     *
     * @param string $streamId
     *
     * @return $this
     */
    public function streamId($streamId)
    {
        $this->streamId = $streamId;

        return $this;
    }

    /**
     * Set the limit per request.
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
     * Enable stream on the table.
     *
     * @param int $expirationTime Expiration time in hours (default: 24)
     *
     * @return $this
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function enableStream($expirationTime = 24)
    {
        $this->connection->updateTable([
            'table_name' => $this->tableName,
            'stream_spec' => [
                'enable_stream' => true,
                'expiration_time' => $expirationTime,
            ],
        ]);

        return $this;
    }

    /**
     * Disable stream on the table.
     *
     * @return $this
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function disableStream()
    {
        $this->connection->updateTable([
            'table_name' => $this->tableName,
            'stream_spec' => [
                'enable_stream' => false,
            ],
        ]);

        return $this;
    }

    /**
     * List all streams for the table.
     *
     * @return array
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function listStreams()
    {
        $response = $this->connection->listStream([
            'table_name' => $this->tableName,
        ]);

        return $response['streams'] ?? [];
    }

    /**
     * Get the first (or only) stream for the table.
     *
     * @return array|null
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function getStream()
    {
        $streams = $this->listStreams();

        return $streams[0] ?? null;
    }

    /**
     * Get or auto-discover the stream ID.
     *
     * @return string|null
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function getStreamId()
    {
        if ($this->streamId !== null) {
            return $this->streamId;
        }

        $stream = $this->getStream();
        if ($stream !== null) {
            $this->streamId = $stream['stream_id'];
        }

        return $this->streamId;
    }

    /**
     * Describe the stream.
     *
     * @param string|null $streamId
     *
     * @return array
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function describeStream($streamId = null)
    {
        $streamId = $streamId ?? $this->getStreamId();

        if ($streamId === null) {
            throw new OTSClientException('No stream ID available. Enable stream first.');
        }

        return $this->connection->describeStream([
            'stream_id' => $streamId,
        ]);
    }

    /**
     * Get all shards for the stream.
     *
     * @return array
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function getShards()
    {
        $description = $this->describeStream();

        return $description['shards'] ?? [];
    }

    /**
     * Get shard iterator for a specific shard.
     *
     * @param string $shardId The shard ID
     * @param string|null $timestamp Start from a specific timestamp (optional)
     *
     * @return string
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function getShardIterator($shardId, $timestamp = null)
    {
        $streamId = $this->getStreamId();

        if ($streamId === null) {
            throw new OTSClientException('No stream ID available.');
        }

        $request = [
            'stream_id' => $streamId,
            'shard_id' => $shardId,
        ];

        if ($timestamp !== null) {
            $request['timestamp'] = $timestamp;
        }

        $response = $this->connection->getShardIterator($request);

        return $response['shard_iterator'];
    }

    /**
     * Get stream records starting from an iterator.
     *
     * @param string $iterator The shard iterator
     *
     * @return array
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function getRecords($iterator)
    {
        $response = $this->connection->getStreamRecord([
            'shard_iterator' => $iterator,
            'limit' => $this->limit,
        ]);

        $records = [];
        foreach ($response['stream_records'] ?? [] as $record) {
            $records[] = new StreamRecord($record);
        }

        return [
            'records' => $records,
            'next_iterator' => $response['next_shard_iterator'] ?? null,
        ];
    }

    /**
     * Read all records from a shard.
     *
     * @param string $shardId The shard ID
     *
     * @return Generator
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function readShard($shardId)
    {
        $iterator = $this->getShardIterator($shardId);

        while ($iterator !== null) {
            $result = $this->getRecords($iterator);

            foreach ($result['records'] as $record) {
                yield $record;
            }

            $iterator = $result['next_iterator'];

            // If no records and no next iterator, break
            if (empty($result['records']) && $iterator === null) {
                break;
            }
        }
    }

    /**
     * Read all records from all shards.
     *
     * @return Generator
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function readAll()
    {
        $shards = $this->getShards();

        foreach ($shards as $shard) {
            yield from $this->readShard($shard['shard_id']);
        }
    }

    /**
     * Collect all records from all shards.
     *
     * @return Collection
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function collect()
    {
        $records = [];

        foreach ($this->readAll() as $record) {
            $records[] = $record;
        }

        return new Collection($records);
    }

    /**
     * Watch for new records (long polling style).
     *
     * @param callable $callback Function to call for each record
     * @param int $pollInterval Interval between polls in seconds
     * @param int $maxIterations Maximum number of iterations (0 = infinite)
     *
     * @return void
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function watch($callback, $pollInterval = 1, $maxIterations = 0)
    {
        $shards = $this->getShards();
        $iterators = [];

        // Initialize iterators for all shards
        foreach ($shards as $shard) {
            $iterators[$shard['shard_id']] = $this->getShardIterator($shard['shard_id']);
        }

        $iterations = 0;

        while (true) {
            $hasRecords = false;

            foreach ($iterators as $shardId => $iterator) {
                if ($iterator === null) {
                    continue;
                }

                $result = $this->getRecords($iterator);

                foreach ($result['records'] as $record) {
                    $hasRecords = true;
                    $shouldContinue = $callback($record, $shardId);

                    if ($shouldContinue === false) {
                        return;
                    }
                }

                $iterators[$shardId] = $result['next_iterator'];
            }

            $iterations++;

            if ($maxIterations > 0 && $iterations >= $maxIterations) {
                break;
            }

            if (!$hasRecords) {
                sleep($pollInterval);
            }
        }
    }
}
