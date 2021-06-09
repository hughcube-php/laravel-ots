<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/18
 * Time: 10:32 下午.
 */

namespace HughCube\Laravel\OTS;

use HughCube\Laravel\OTS\Cache\Store;
use HughCube\Laravel\OTS\Commands\CacheGc;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Boot the provider.
     */
    public function boot()
    {
    }

    /**
     * Register the provider.
     */
    public function register()
    {
        $this->registerDatabaseExtend();
        $this->registerCacheExtend();
        $this->registerCommand();
    }

    protected function registerDatabaseExtend()
    {
        $this->app->resolving('db', function ($db) {
            /** @var \Illuminate\Database\DatabaseManager $db */
            $db->extend('ots', function ($config, $name) {
                $config['name'] = $name;
                return new Connection($config);
            });
        });
    }

    protected function registerCacheExtend()
    {
        $this->app->resolving('cache', function ($cache) {
            /** @var \Illuminate\Cache\CacheManager $cache */
            $cache->extend('ots', function ($app, $config) {
                /** @var \Illuminate\Cache\CacheManager $this */

                /** @var Connection $connection */
                $connection = $app['db']->connection($config['connection']);

                $prefix = $config['prefix'] ?? $app['config']['cache.prefix'];
                $indexTable = $config['indexTable'] ?? null;
                $store = new Store($connection->getOts(), $config['table'], $prefix, $indexTable);
                return $this->repository($store);
            });
        });
    }

    protected function registerCommand()
    {
        $this->commands([
            CacheGc::class
        ]);
    }
}
