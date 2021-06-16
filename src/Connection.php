<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/8
 * Time: 5:47 下午.
 */

namespace HughCube\Laravel\OTS;

use Aliyun\OTS\OTSClient;
use HughCube\Laravel\AlibabaCloud\AlibabaCloud;
use HughCube\Laravel\AlibabaCloud\Client as AlibabaCloudClient;
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
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $this->formatConfig($config);

        // Create the otsClient
        $this->ots = $this->createConnection($config);
    }

    /**
     * @param array|string $config
     * @return mixed|string[]
     */
    protected function formatConfig($config)
    {
        if (is_string($config)) {
            $config = ['alibabaCloud' => $config];
        }

        $alibabaCloud = null;
        if (Arr::has($config, 'alibabaCloud') && $config['alibabaCloud'] instanceof AlibabaCloudClient) {
            $alibabaCloud = $config['alibabaCloud'];
        } elseif (Arr::has($config, 'alibabaCloud')) {
            $alibabaCloud = AlibabaCloud::client($config['alibabaCloud']);
        }

        /** AccessKeyID */
        if (empty($config['AccessKeyID']) && null !== $alibabaCloud) {
            $config['AccessKeyID'] = $alibabaCloud->getAccessKeyId();
        }

        /** AccessKeySecret */
        if (empty($config['AccessKeySecret']) && null !== $alibabaCloud) {
            $config['AccessKeySecret'] = $alibabaCloud->getAccessKeySecret();
        }

        return $config;
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
     *
     * @param array $config
     *
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
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->getOts(), $method], $parameters);
    }
}
