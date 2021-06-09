<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/8
 * Time: 11:30 下午.
 */

namespace HughCube\Laravel\OTS\Tests\Cache;

use Closure;
use HughCube\Laravel\OTS\Cache\Lock;
use HughCube\Laravel\OTS\Cache\Store;
use HughCube\Laravel\OTS\Tests\TestCase;
use Illuminate\Contracts\Cache\Lock as IlluminateLock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\Facades\Cache;

class LockTest extends TestCase
{
    public function testInstanceOf()
    {
        $this->assertInstanceOf(LockProvider::class, $this->getStore());

        $this->assertInstanceOf(
            IlluminateLock::class,
            $this->getStore()->lock(md5(random_bytes(100)), 10)
        );
    }

    public function testLock()
    {
        $name = md5(random_bytes(100));

        $lock = $this->getStore()->lock($name, 10);
        $this->assertTrue($lock->acquire());
        $this->assertTrue($lock->acquire());

        $lock = $this->getStore()->lock($name, 10);
        $this->assertFalse($lock->acquire());

        sleep(11);
        $lock = $this->getStore()->lock($name, 10);
        $this->assertTrue($lock->acquire());
    }

    public function testRelease()
    {
        $name = md5(random_bytes(100));

        $lock = $this->getStore()->lock($name, 10);
        $this->assertTrue($lock->acquire());
        $this->assertTrue($lock->release());

        $this->assertTrue($lock->acquire());
        $this->assertTrue($lock->release());

        $lock = $this->getStore()->lock($name, 10);
        $this->assertTrue($lock->acquire());

        sleep(11);
        $this->assertTrue($lock->release());
    }

    public function testForceRelease()
    {
        $name = md5(random_bytes(100));

        $lock = $this->getStore()->lock($name, 10);
        $this->assertTrue($lock->forceRelease());

        $this->assertTrue($lock->acquire());

        $lock = $this->getStore()->lock($name, 10);
        $this->assertFalse($lock->acquire());
        $this->assertTrue($lock->forceRelease());
        $this->assertTrue($lock->acquire());

        sleep(11);
        $this->assertTrue($lock->forceRelease());
    }

    public function testGetCurrentOwner()
    {
        $name = md5(random_bytes(100));
        $lock = $this->getStore()->lock($name, 10);
        $getCurrentOwner = Closure::bind(function () {
            /** @var Lock $this */
            return $this->getCurrentOwner();
        }, $lock, Lock::class);

        $this->assertNotSame($lock->owner(), $getCurrentOwner());

        $this->assertTrue($lock->acquire());
        $this->assertSame($lock->owner(), $getCurrentOwner());

        sleep(11);
        $this->assertNull($getCurrentOwner());
    }

    /**
     * @return Store
     */
    protected function getStore()
    {
        /** @var Store $store */
        $store = Cache::store('ots')->getStore();

        return $store;
    }
}
