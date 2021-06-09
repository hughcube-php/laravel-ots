<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/8
 * Time: 5:47 下午
 */

namespace HughCube\Laravel\OTS;

use Aliyun\OTS\OTSClient;
use Illuminate\Database\Connection as IlluminateConnection;
use Illuminate\Support\Arr;

class Connection extends IlluminateConnection
{
    /**
     * @var OTSClient
     */
    protected $ots;

    /**
     * Create a new database connection instance.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        // Create the otsClient
        $this->ots = $this->createConnection($config);
    }

    /**
     * @return OtsClient
     */
    public function getOts()
    {
        return $this->ots;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabaseName()
    {
        return $this->getOts()->getClientConfig()->getInstanceName();
    }

    /**
     * Create a new OTSClient connection.
     * @param array $config
     * @return OTSClient
     */
    protected function createConnection(array $config)
    {
        $config['ErrorLogHandler'] = Arr::get($config, 'ErrorLogHandler', false);
        $config['DebugLogHandler'] = Arr::get($config, 'DebugLogHandler', false);

        return new OTSClient($config);
    }

    /**
     * @inheritdoc
     */
    public function disconnect()
    {
        unset($this->ots);
    }

    /**
     * @inheritdoc
     */
    public function getDriverName()
    {
        return 'ots';
    }

    /**
     * Dynamically pass methods to the connection.
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->getOts(), $method], $parameters);
    }
}
