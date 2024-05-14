<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/8
 * Time: 5:47 下午.
 */

namespace HughCube\Laravel\OTS;

use Aliyun\OTS\Consts\PrimaryKeyTypeConst;
use Aliyun\OTS\OTSClient;
use Aliyun\OTS\OTSClientException;
use Aliyun\OTS\OTSServerException;
use DateTimeInterface;
use Exception;
use HughCube\Laravel\OTS\Exceptions\PartialSuccessResponseException;
use HughCube\Laravel\OTS\OTS\Handlers\OTSHandlers;
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
     * @param  array  $config
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
     * @throws OTSClientException
     * @throws OTSServerException
     * @throws Exception
     */
    public function putRowAndReturnId(array $request): ?int
    {
        /** 解析出来自增id属性名 */
        $name = null;
        foreach ($request['primary_key'] as $primary) {
            $primary = array_values($primary);
            if (isset($primary[2]) && PrimaryKeyTypeConst::CONST_PK_AUTO_INCR === $primary[2]) {
                $name = $primary[0];
            }
        }
        if (null == $name) {
            throw new Exception('The self-increasing primary key is not declared!');
        }

        /** 插入数据 */
        $response = $this->putRow($request);

        /** 解析出来自增id */
        foreach ($response['primary_key'] ?? [] as $primary) {
            $primary = array_values($primary);
            if (isset($primary[0], $primary[1]) && $name === $primary[0] && is_int($primary[1])) {
                return $primary[1];
            }
        }

        return null;
    }

    /**
     * @throws OTSClientException
     * @throws OTSServerException
     * @throws PartialSuccessResponseException
     */
    public function mustBatchWriteRow(array $request): array
    {
        $response = $this->batchWriteRow($request);

        foreach ($response['tables'] as $table) {
            foreach ($table['rows'] as $row) {
                if (empty($row['is_ok'])) {
                    throw new PartialSuccessResponseException(
                        $response,
                        sprintf('Failed to write the "%s" table.', $table['table_name'])
                    );
                }
            }
        }

        return $response;
    }

    /**
     * @param  mixed  $row
     * @param  string  $name
     *
     * @return null|int
     *
     * @throws Exception
     * @deprecated
     */
    public function parseAutoIncId($row, string $name = 'id'): ?int
    {
        return Ots::parseRowAutoId($row, $name);
    }

    /**
     * @param  mixed  $row
     * @return array
     * @deprecated
     */
    public function parseRowColumns($row): array
    {
        return Ots::parseRow($row);
    }

    /**
     * @throws Exception
     * @deprecated
     */
    public function mustParseRowAutoId($row, string $name = 'id'): int
    {
        return Ots::mustParseRowAutoId($row, $name);
    }

    /**
     * @param $response
     * @return bool
     * @deprecated
     */
    public function isSuccessBatchWriteResponse($response): bool
    {
        return Ots::isBatchWriteSuccess($response);
    }

    /**
     * @throws Exception
     * @deprecated
     */
    public function assertSuccessBatchWriteResponse($response)
    {
        Ots::throwBatchWriteException($response);
    }

    /**
     * @param  int  $delay
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
     * @param  mixed  $date
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

    public function asyncSearch($request): OTS\Handlers\RequestContext
    {
        /** @phpstan-ignore-next-line */
        $proxy = new OTSHandlers($this->getOts()->handlers);
        return $proxy->asyncDoHandle("Search", $request);
    }
}
