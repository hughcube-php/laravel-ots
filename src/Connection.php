<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/8
 * Time: 5:47 下午.
 */

namespace HughCube\Laravel\OTS;

use Aliyun\OTS\OTSClient;
use DateTimeInterface;
use Exception;
use Illuminate\Database\Connection as IlluminateConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * @mixin OTSClient
 */
class Connection extends IlluminateConnection
{
    /**
     * @var null|OTSClient
     */
    private $ots = null;

    /**
     * Create a new database connection instance.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        $this->useDefaultPostProcessor();

        $this->useDefaultSchemaGrammar();

        $this->useDefaultQueryGrammar();
    }

    /**
     * @return OtsClient
     */
    public function getOts(): OTSClient
    {
        if (!$this->ots instanceof OTSClient) {
            $this->ots = $this->createConnection($this->config);
        }

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
    protected function createConnection(array $config): OTSClient
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
        $this->ots = null;
    }

    /**
     * @inheritdoc
     */
    public function getDriverName(): string
    {
        return 'ots';
    }

    /**
     * @param mixed  $row
     * @param string $name
     *
     * @throws Exception
     *
     * @return null|int
     *
     * @deprecated 放在Ots实现
     */
    public function parseAutoIncId($row, string $name = 'id'): ?int
    {
        return Ots::parseRowAutoId($row, $name);
    }

    /**
     * @param mixed $row
     *
     * @return array
     *
     * @deprecated 放在Ots实现
     */
    public function parseRowColumns($row): array
    {
        return Ots::parseRow($row);
    }

    /**
     * @param int $delay
     *
     * @return string
     *
     * @deprecated 放在Knight依赖实现里面
     */
    public function availableDate(int $delay = 0): string
    {
        return Carbon::now()->addRealSeconds($delay)->format(DateTimeInterface::RFC3339_EXTENDED);
    }

    /**
     * @param mixed $date
     *
     * @return Carbon|null
     *
     * @deprecated 放在Knight依赖实现里面
     */
    public function availableDateToDateTime($date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        $dateTime = Carbon::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $date);

        return $dateTime instanceof Carbon ? $dateTime : null;
    }

    /**
     * @inheritdoc
     * @return Schema\Builder
     */
    public function getSchemaBuilder(): Schema\Builder
    {
        return new Schema\Builder($this);
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultSchemaGrammar()
    {
        return new Schema\Grammar();
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultPostProcessor()
    {
        return new Query\Processor();
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultQueryGrammar()
    {
        return new Query\Grammar();
    }

    /**
     * @inheritDoc
     */
    public function __call($method, $parameters = [])
    {
        if (method_exists($this->getOts(), $method)) {
            return $this->getOts()->$method(...$parameters);
        }

        return parent::__call($method, $parameters);
    }
}
