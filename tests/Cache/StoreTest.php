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
use Exception;
use HughCube\Laravel\OTS\Cache\Store;
use HughCube\Laravel\OTS\Tests\TestCase;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as IlluminateRepository;
use Illuminate\Contracts\Cache\Store as IlluminateStore;
use Illuminate\Support\Facades\Cache;

class StoreTest extends TestCase
{
    public function testInstanceOf()
    {
        $this->assertInstanceOf(IlluminateRepository::class, $this->getCache());

        $this->assertInstanceOf(IlluminateStore::class, $this->getStore());
        $this->assertInstanceOf(LockProvider::class, $this->getStore());
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     * @throws Exception
     */
    public function testGet()
    {
        $key = md5(serialize([random_bytes(100), time()]));
        $value = random_bytes(100);

        $this->assertTrue($this->getStore()->put($key, $value, 10));
        $this->assertSame($this->getStore()->get($key), $value);

        sleep(11);
        $this->assertNull($this->getStore()->get($key));

        $this->assertNull($this->getStore()->get($key));
    }

    /**
     * @throws OTSClientException
     * @throws OTSServerException
     * @throws Exception
     *
     * @return void
     */
    public function testPut()
    {
        $this->assertTrue(
            $this->getStore()->put(md5(random_bytes(100)), random_bytes(100), 10)
        );
    }

    /**
     * @throws OTSClientException
     * @throws OTSServerException
     * @throws Exception
     *
     * @return void
     */
    public function testAdd()
    {
        $key = md5(serialize([random_bytes(100), time()]));
        $value = random_bytes(100);

        $this->assertTrue($this->getStore()->add($key, $value, 10));
        $this->assertSame($this->getStore()->get($key), $value);

        sleep(1);
        $this->assertFalse($this->getStore()->add($key, $value, 10));

        sleep(11);
        $this->assertTrue($this->getStore()->add($key, $value, 10));
    }

    /**
     * @throws OTSClientException
     * @throws OTSServerException
     * @throws Exception
     *
     * @return void
     */
    public function testMany()
    {
        $items = [
            md5(serialize([random_bytes(100), time()])) => random_bytes(100),
            md5(serialize([random_bytes(100), time()])) => random_bytes(100),
        ];
        foreach ($items as $key => $value) {
            $this->assertTrue($this->getStore()->put($key, $value, 10));
        }

        $this->assertSame($this->getStore()->many(array_keys($items)), $items);

        sleep(11);
        $this->assertSame($this->getStore()->many(array_keys($items)), array_map(function () {
            return null;
        }, $items));
    }

    /**
     * @throws OTSClientException
     * @throws OTSServerException
     * @throws Exception
     *
     * @return void
     */
    public function testPutMany()
    {
        $items = [];
        for ($i = 1; $i <= 100; $i++) {
            $items[md5(serialize([random_bytes(100), time(), $i]))] = random_bytes(100);
        }

        $this->assertTrue($this->getStore()->putMany($items, 10));
        $this->assertSame($this->getStore()->many(array_keys($items)), $items);

        sleep(11);
        $this->assertSame($this->getStore()->many(array_keys($items)), array_map(function () {
            return null;
        }, $items));
    }

    /**
     * @throws OTSClientException
     * @throws OTSServerException
     * @throws Exception
     *
     * @return void
     */
    public function testIncrement()
    {
        for ($i = 1; $i <= 100; $i++) {
            $key = md5(serialize([random_bytes(100), time()]));
            $value = random_int(-10, 10);

            $this->getStore()->put($key, 0, 100);
            $this->assertEquals($this->getStore()->get($key), 0);

            $newValue = $this->getStore()->increment($key, $value);
            $this->assertEquals($newValue, $value);
            $this->assertEquals($newValue, $this->getStore()->get($key));
        }
    }

    /**
     * @throws OTSClientException
     * @throws OTSServerException
     * @throws Exception
     *
     * @return void
     */
    public function testDecrement()
    {
        for ($i = 1; $i <= 100; $i++) {
            $key = md5(serialize([random_bytes(100), time()]));
            $value = random_int(-10, 10);

            $this->getStore()->put($key, 0, 100);
            $this->assertEquals($this->getStore()->get($key), 0);

            $newValue = $this->getStore()->decrement($key, $value);
            $this->assertEquals($newValue, (0 - $value));
            $this->assertEquals($newValue, $this->getStore()->get($key));
        }
    }

    /**
     * @throws OTSClientException
     * @throws OTSServerException
     * @throws Exception
     *
     * @return void
     */
    public function testForever()
    {
        $key = md5(serialize([random_bytes(100), time()]));
        $value = random_bytes(100);

        $this->assertTrue($this->getStore()->forever($key, $value));
        $this->assertSame($this->getStore()->get($key), $value);
    }

    /**
     * @throws OTSClientException
     * @throws OTSServerException
     * @throws Exception
     *
     * @return void
     */
    public function testForget()
    {
        $value = random_bytes(100);
        $key = md5(serialize([random_bytes(100), time()]));
        $this->assertTrue($this->getStore()->forever($key, $value));
        $this->assertSame($this->getStore()->get($key), $value);
        $this->assertTrue($this->getStore()->forget($key));
        $this->assertNull($this->getStore()->get($key));

        $value = random_bytes(100);
        $key = md5(serialize([random_bytes(100), time()]));
        $this->assertTrue($this->getStore()->put($key, $value, 100));
        $this->assertSame($this->getStore()->get($key), $value);
        $this->assertTrue($this->getStore()->forget($key));
        $this->assertNull($this->getStore()->get($key));
    }

    public function testFlush()
    {
        $this->assertTrue($this->getStore()->flush());
    }

    /**
     * @return Store
     */
    protected function getStore(): Store
    {
        return $this->getCache()->getStore();
    }

    /**
     * @return IlluminateRepository
     */
    protected function getCache(): IlluminateRepository
    {
        return Cache::store('ots');
    }
}
