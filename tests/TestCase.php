<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/20
 * Time: 11:36 下午.
 */

namespace HughCube\Laravel\OTS\Tests;

use Aliyun\OTS\OTSClientException;
use HughCube\Laravel\OTS\Connection;
use HughCube\Laravel\OTS\ServiceProvider;
use Illuminate\Auth\Passwords\PasswordResetServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

/**
 * @method Connection getConnection($connection = null, $table = null)
 */
class TestCase extends OrchestraTestCase
{
    /**
     * @var bool|null
     */
    protected static $networkAvailable = null;

    /**
     * @inheritDoc
     */
    protected function getApplicationProviders($app): array
    {
        $providers = parent::getApplicationProviders($app);

        unset($providers[array_search(PasswordResetServiceProvider::class, $providers)]);

        return $providers;
    }

    /**
     * @inheritDoc
     */
    protected function getPackageProviders($app): array
    {
        return [
            ServiceProvider::class,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('app.key', 'onahM9thZoa2YeikaiChah0jaeToh9Ra');

        $app['config']->set('database', require 'config/database.php');

        $app['config']->set('cache', require 'config/cache.php');
    }

    protected function getTestTable(): string
    {
        return 'tests';
    }

    /**
     * Check if network (OTS) is available.
     */
    protected function isNetworkAvailable(): bool
    {
        if (null === static::$networkAvailable) {
            try {
                $this->getConnection()->listTable([]);
                static::$networkAvailable = true;
            } catch (OTSClientException $e) {
                static::$networkAvailable = false;
            } catch (\Aliyun\OTS\OTSServerException $e) {
                // ACL policy denial or authentication failure
                static::$networkAvailable = false;
            } catch (\Exception $e) {
                static::$networkAvailable = false;
            }
        }

        return static::$networkAvailable;
    }

    /**
     * Skip test if network is not available.
     */
    protected function skipIfNetworkUnavailable(): void
    {
        if (!$this->isNetworkAvailable()) {
            $this->markTestSkipped('Network (OTS) is not available.');
        }
    }

    /**
     * Ensure cache table exists, create if not.
     */
    protected function ensureCacheTableExists(): void
    {
        if (!$this->isNetworkAvailable()) {
            return;
        }

        try {
            $builder = $this->getConnection()->getSchemaBuilder();
            if (!$builder->hasTable('cache')) {
                $builder->create('cache', function (\HughCube\Laravel\OTS\Schema\Blueprint $table) {
                    $table->char('key')->primary();
                    $table->char('prefix')->primary();
                    $table->char('type')->primary();
                });
                // Wait for table to be ready
                sleep(2);
            }
        } catch (\Exception $e) {
            // Table might already exist or creation failed
        }
    }

    /**
     * Skip test if cache table does not exist and cannot be created.
     */
    protected function skipIfCacheTableNotExists(): void
    {
        $this->ensureCacheTableExists();

        try {
            if (!$this->getConnection()->getSchemaBuilder()->hasTable('cache')) {
                $this->markTestSkipped('Cache table does not exist and cannot be created.');
            }
        } catch (\Exception $e) {
            $this->markTestSkipped('Cache table check failed: ' . $e->getMessage());
        }
    }

    /**
     * Ensure cache index exists, create if not.
     */
    protected function ensureCacheIndexExists(): void
    {
        $this->ensureCacheTableExists();

        if (!$this->isNetworkAvailable()) {
            return;
        }

        try {
            $indexes = $this->getConnection()->listSearchIndex(['table_name' => 'cache']);
            $hasIndex = false;
            if (!empty($indexes)) {
                foreach ($indexes as $index) {
                    if (isset($index['index_name']) && $index['index_name'] === 'cache_index') {
                        $hasIndex = true;
                        break;
                    }
                }
            }

            if (!$hasIndex) {
                $this->getConnection()->createSearchIndex([
                    'table_name' => 'cache',
                    'index_name' => 'cache_index',
                    'schema' => [
                        'field_schemas' => [
                            [
                                'field_name' => 'key',
                                'field_type' => \Aliyun\OTS\Consts\FieldTypeConst::KEYWORD,
                            ],
                            [
                                'field_name' => 'prefix',
                                'field_type' => \Aliyun\OTS\Consts\FieldTypeConst::KEYWORD,
                            ],
                            [
                                'field_name' => 'type',
                                'field_type' => \Aliyun\OTS\Consts\FieldTypeConst::KEYWORD,
                            ],
                        ],
                    ],
                ]);
                // Wait for index to be ready
                sleep(5);
            }
        } catch (\Exception $e) {
            // Index might already exist or creation failed
        }
    }

    /**
     * Skip test if cache index does not exist and cannot be created.
     */
    protected function skipIfCacheIndexNotExists(): void
    {
        $this->ensureCacheIndexExists();

        try {
            $indexes = $this->getConnection()->listSearchIndex(['table_name' => 'cache']);
            if (empty($indexes)) {
                $this->markTestSkipped('Cache index does not exist and cannot be created.');
            }
        } catch (\Exception $e) {
            $this->markTestSkipped('Cache index check failed: ' . $e->getMessage());
        }
    }

    /**
     * Skip test if SQL is not available for cache table.
     */
    protected function skipIfSqlNotAvailable(): void
    {
        $this->ensureCacheTableExists();

        try {
            // Try a simple SQL query to check if SQL is available
            $this->getConnection()->sqlQuery([
                'query' => 'SHOW TABLES',
            ]);
        } catch (\Aliyun\OTS\OTSServerException $e) {
            // If SQL is not enabled, skip the test
            if (strpos($e->getMessage(), 'doesn\'t exist') !== false
                || strpos($e->getMessage(), 'OTSParameterInvalid') !== false
                || strpos($e->getMessage(), 'SQL') !== false) {
                $this->markTestSkipped('SQL is not available: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            $this->markTestSkipped('SQL check failed: ' . $e->getMessage());
        }
    }
}
