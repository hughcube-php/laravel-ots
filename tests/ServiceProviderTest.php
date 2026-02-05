<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/3
 * Time: 17:08.
 */

namespace HughCube\Laravel\OTS\Tests;

use HughCube\Laravel\OTS\Cache\Store;
use HughCube\Laravel\OTS\Connection;
use HughCube\Laravel\OTS\ServiceProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ServiceProviderTest extends TestCase
{
    public function testServiceProviderIsRegistered()
    {
        $providers = $this->app->getLoadedProviders();
        $this->assertArrayHasKey(ServiceProvider::class, $providers);
    }

    public function testDatabaseExtendIsRegistered()
    {
        $connection = DB::connection('ots');
        $this->assertInstanceOf(Connection::class, $connection);
    }

    public function testCacheExtendIsRegistered()
    {
        $cache = Cache::store('ots');
        $this->assertInstanceOf(Repository::class, $cache);

        $store = $cache->getStore();
        $this->assertInstanceOf(Store::class, $store);
    }

    public function testCacheStoreHasCorrectTable()
    {
        $cache = Cache::store('ots');
        $store = $cache->getStore();

        $this->assertSame('cache', $store->getTable());
    }

    public function testCacheStoreHasCorrectPrefix()
    {
        $cache = Cache::store('ots');
        $store = $cache->getStore();

        $prefix = $store->getPrefix();
        $this->assertIsString($prefix);
    }

    public function testCacheStoreUsesOtsConnection()
    {
        $cache = Cache::store('ots');
        $store = $cache->getStore();

        $ots = $store->getOts();
        $this->assertInstanceOf(Connection::class, $ots);
    }

    public function testCommandsAreRegistered()
    {
        $commands = $this->app['Illuminate\Contracts\Console\Kernel']->all();

        $this->assertArrayHasKey('ots:gc-cache', $commands);
        $this->assertArrayHasKey('ots:clear-table', $commands);
    }
}
