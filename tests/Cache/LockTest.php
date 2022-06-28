<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/8
 * Time: 11:30 下午.
 */

namespace HughCube\Laravel\OTS\Tests\Cache;

use Aliyun\OTS\OTSClientException;
use Aliyun\OTS\OTSServerException;
use Closure;
use Exception;
use HughCube\Laravel\OTS\Cache\Lock;
use HughCube\Laravel\OTS\Cache\Store;
use HughCube\Laravel\OTS\Tests\TestCase;
use Illuminate\Contracts\Cache\Lock as IlluminateLock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\Facades\Cache;

class LockTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testInstanceOf()
    {
        $this->assertInstanceOf(LockProvider::class, $this->getStore());

        $this->assertInstanceOf(IlluminateLock::class,
            $this->getStore()->lock(md5(random_bytes(100)), 10)
        );
    }

    /**
     * @return void
     * @throws OTSServerException
     *
     * @throws OTSClientException
     * @throws Exception
     */
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

    /**
     * @return void
     * @throws OTSServerException
     * @throws Exception
     *
     * @throws OTSClientException
     */
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

    /**
     * @return void
     * @throws OTSServerException
     * @throws Exception
     *
     * @throws OTSClientException
     */
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

    /**
     * @return void
     * @throws OTSServerException
     * @throws Exception
     *
     * @throws OTSClientException
     */
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
        $this->assertEmpty($getCurrentOwner());
    }

    /**
     * @return Store
     */
    protected function getStore(): Store
    {
        return Cache::store('ots')->getStore();
    }
}
