<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/20
 * Time: 11:36 下午.
 */

namespace HughCube\Laravel\OTS\Tests;

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

        $app['config']->set('database', (require 'config/database.php'));

        $app['config']->set('cache', (require 'config/cache.php'));
    }

    protected function getTestTable(): string
    {
        return 'tests';
    }
}
