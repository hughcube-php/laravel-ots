<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/18
 * Time: 10:32 下午.
 */

namespace HughCube\Laravel\OTS;

use HughCube\Laravel\OTS\Cache\Store;
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
        $this->app->resolving('db', function ($db) {
            /** @var \Illuminate\Database\DatabaseManager $db */
            $db->extend('ots', function ($config, $name) {
                $config['name'] = $name;
                return new Connection($config);
            });
        });

        $this->app->resolving('cache', function ($cache) {
            /** @var \Illuminate\Cache\CacheManager $cache */
            $cache->extend('ots', function ($app, $config) {
                /** @var \Illuminate\Cache\CacheManager $this */

                /** @var Connection $connection */
                $connection = $app['db']->connection($config['connection']);

                $prefix = $config['prefix'] ?? $app['config']['cache.prefix'];
                $store = new Store($connection->getOts(), $config['table'], $prefix);
                return $this->repository($store);
            });
        });
    }
}
