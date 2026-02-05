<?php

namespace HughCube\Laravel\OTS;

use Aliyun\OTS\OTSClientException;
use Aliyun\OTS\OTSServerException;
use Throwable;

class Transaction
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string|null
     */
    protected $transactionId = null;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var array
     */
    protected $partitionKey;

    /**
     * @param Connection $connection
     * @param string $tableName
     * @param array $partitionKey
     */
    public function __construct(Connection $connection, $tableName, array $partitionKey)
    {
        $this->connection = $connection;
        $this->tableName = $tableName;
        $this->partitionKey = $partitionKey;
    }

    /**
     * Get the transaction ID.
     *
     * @return string|null
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * Check if the transaction is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->transactionId !== null;
    }

    /**
     * Start the transaction.
     *
     * @return $this
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function begin()
    {
        if ($this->transactionId !== null) {
            throw new OTSClientException('Transaction already started.');
        }

        $response = $this->connection->startLocalTransaction([
            'table_name' => $this->tableName,
            'key' => $this->formatPartitionKey(),
        ]);

        $this->transactionId = $response['transaction_id'];

        return $this;
    }

    /**
     * Commit the transaction.
     *
     * @return void
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function commit()
    {
        if ($this->transactionId === null) {
            throw new OTSClientException('No active transaction to commit.');
        }

        $this->connection->commitTransaction([
            'transaction_id' => $this->transactionId,
        ]);

        $this->transactionId = null;
    }

    /**
     * Rollback (abort) the transaction.
     *
     * @return void
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function rollback()
    {
        if ($this->transactionId === null) {
            return;
        }

        $this->connection->abortTransaction([
            'transaction_id' => $this->transactionId,
        ]);

        $this->transactionId = null;
    }

    /**
     * Alias for rollback.
     *
     * @return void
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function abort()
    {
        $this->rollback();
    }

    /**
     * Put a row within the transaction.
     *
     * @param array $request
     *
     * @return array
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function putRow(array $request)
    {
        $this->ensureActiveTransaction();

        $request['transaction_id'] = $this->transactionId;

        return $this->connection->putRow($request);
    }

    /**
     * Get a row within the transaction.
     *
     * @param array $request
     *
     * @return array
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function getRow(array $request)
    {
        $this->ensureActiveTransaction();

        $request['transaction_id'] = $this->transactionId;

        return $this->connection->getRow($request);
    }

    /**
     * Update a row within the transaction.
     *
     * @param array $request
     *
     * @return array
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function updateRow(array $request)
    {
        $this->ensureActiveTransaction();

        $request['transaction_id'] = $this->transactionId;

        return $this->connection->updateRow($request);
    }

    /**
     * Delete a row within the transaction.
     *
     * @param array $request
     *
     * @return array
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function deleteRow(array $request)
    {
        $this->ensureActiveTransaction();

        $request['transaction_id'] = $this->transactionId;

        return $this->connection->deleteRow($request);
    }

    /**
     * Batch write rows within the transaction.
     *
     * @param array $request
     *
     * @return array
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function batchWriteRow(array $request)
    {
        $this->ensureActiveTransaction();

        $request['transaction_id'] = $this->transactionId;

        return $this->connection->batchWriteRow($request);
    }

    /**
     * Get range within the transaction.
     *
     * @param array $request
     *
     * @return array
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function getRange(array $request)
    {
        $this->ensureActiveTransaction();

        $request['transaction_id'] = $this->transactionId;

        return $this->connection->getRange($request);
    }

    /**
     * Ensure there is an active transaction.
     *
     * @return void
     *
     * @throws OTSClientException
     */
    protected function ensureActiveTransaction()
    {
        if ($this->transactionId === null) {
            throw new OTSClientException('No active transaction. Call begin() first.');
        }
    }

    /**
     * Format the partition key for the transaction request.
     *
     * @return array
     */
    protected function formatPartitionKey()
    {
        $formatted = [];
        foreach ($this->partitionKey as $key => $value) {
            if (is_array($value)) {
                $formatted[] = $value;
            } else {
                $formatted[] = [$key, $value];
            }
        }

        return $formatted;
    }

    /**
     * Destructor - rollback if transaction is still active.
     */
    public function __destruct()
    {
        if ($this->transactionId !== null) {
            try {
                $this->rollback();
            } catch (Throwable $e) {
                // Ignore errors during destructor cleanup
            }
        }
    }
}
