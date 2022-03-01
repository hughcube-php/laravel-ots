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
     * @var OTSClient
     */
    private $ots;

    /**
     * Create a new database connection instance.
     *
     * @param  array  $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
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
     * @param  array  $config
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
        unset($this->ots);
    }

    /**
     * @inheritdoc
     */
    public function getDriverName(): string
    {
        return 'ots';
    }

    /**
     * @param  mixed  $response
     * @param  string  $name
     * @return int
     * @throws Exception
     */
    public function parseAutoIncId($response, string $name = 'id'): int
    {
        $id = null;
        foreach (($response['primary_key'] ?? []) as $key) {
            if ($name === Arr::get($key, 0) && !empty($key[1]) && is_int($key[1])) {
                $id = $key[1];
            }
        }

        if (empty($id) || !is_int($id)) {
            throw new Exception('Failed to obtain the ID!');
        }

        return $id;
    }

    /**
     * @param  mixed  $row
     * @return array
     */
    public function parseRowColumns($row): array
    {
        $row = array_merge(
            Arr::get($row, 'primary_key', []),
            Arr::get($row, 'attribute_columns', [])
        );

        $columns = [];
        foreach ($row as $item) {
            if (isset($item[0], $item[1])) {
                $columns[$item[0]] = $item[1];
            }
        }
        return $columns;
    }

    /**
     * @param  int  $delay
     * @return string
     */
    public function availableDate(int $delay = 0): string
    {
        return Carbon::now()->addRealSeconds($delay)->format(DateTimeInterface::RFC3339_EXTENDED);
    }

    /**
     * @param  mixed  $date
     * @return Carbon|null
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
     * Dynamically pass methods to the connection.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters = [])
    {
        return call_user_func_array([$this->getOts(), $method], $parameters);
    }
}
