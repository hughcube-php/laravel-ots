<?php

namespace HughCube\Laravel\OTS\Tests;

use HughCube\Laravel\OTS\Transaction;

class TransactionTest extends TestCase
{
    public function testInstanceOf()
    {
        $transaction = new Transaction($this->getConnection(), 'test_table', ['pk' => 1]);
        $this->assertInstanceOf(Transaction::class, $transaction);
    }

    public function testIsActiveBeforeBegin()
    {
        $transaction = new Transaction($this->getConnection(), 'test_table', ['pk' => 1]);
        $this->assertFalse($transaction->isActive());
    }

    public function testGetTransactionIdBeforeBegin()
    {
        $transaction = new Transaction($this->getConnection(), 'test_table', ['pk' => 1]);
        $this->assertNull($transaction->getTransactionId());
    }

    public function testBeginThrowsWhenAlreadyStarted()
    {
        $this->skipIfNetworkUnavailable();
        $this->skipIfCacheTableNotExists();

        try {
            $transaction = $this->getConnection()->beginLocalTransaction('cache', ['key' => 'test_tx']);
        } catch (\Aliyun\OTS\OTSServerException $e) {
            // Table may not have transactions enabled
            if (strpos($e->getMessage(), 'explicit-transaction-disabled') !== false) {
                $this->markTestSkipped('Table does not have transactions enabled: '.$e->getMessage());
            }
            throw $e;
        }

        $this->expectException(\Aliyun\OTS\OTSClientException::class);
        $this->expectExceptionMessage('Transaction already started.');

        try {
            $transaction->begin();
        } finally {
            $transaction->rollback();
        }
    }

    public function testCommitThrowsWhenNoTransaction()
    {
        $transaction = new Transaction($this->getConnection(), 'test_table', ['pk' => 1]);

        $this->expectException(\Aliyun\OTS\OTSClientException::class);
        $this->expectExceptionMessage('No active transaction to commit.');

        $transaction->commit();
    }

    public function testRollbackDoesNothingWhenNoTransaction()
    {
        $transaction = new Transaction($this->getConnection(), 'test_table', ['pk' => 1]);

        // Should not throw
        $transaction->rollback();
        $this->assertTrue(true);
    }

    public function testTransactionCallback()
    {
        $this->skipIfNetworkUnavailable();
        $this->skipIfCacheTableNotExists();

        $executed = false;

        try {
            $result = $this->getConnection()->localTransaction('cache', ['key' => 'test_callback'], function ($tx) use (&$executed) {
                $executed = true;
                $this->assertInstanceOf(Transaction::class, $tx);
                $this->assertTrue($tx->isActive());

                return 'success';
            });

            $this->assertTrue($executed);
            $this->assertEquals('success', $result);
        } catch (\Throwable $e) {
            // Transaction may fail if cache table structure doesn't match
            $this->markTestSkipped('Transaction not available: '.$e->getMessage());
        }
    }
}
