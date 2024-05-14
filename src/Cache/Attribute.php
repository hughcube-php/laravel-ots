<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/8
 * Time: 11:02 下午.
 */

namespace HughCube\Laravel\OTS\Cache;

use Aliyun\OTS\Consts\ColumnTypeConst;
use Carbon\Carbon;
use HughCube\Laravel\OTS\Connection;
use HughCube\Laravel\OTS\Ots;
use Illuminate\Support\InteractsWithTime;

trait Attribute
{
    use InteractsWithTime;

    /**
     * @var Connection
     */
    protected $ots;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var null|string
     */
    protected $prefix;

    /**
     * @var null|string
     */
    protected $type;

    /**
     * @var string
     */
    protected $owner;

    /**
     * @return string|null
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @return Connection
     */
    public function getOts(): Connection
    {
        return $this->ots;
    }

    /**
     * @param string $key
     *
     * @return array
     */
    protected function makePrimaryKey(string $key): array
    {
        return [
            ['key', $key],
            ['prefix', $this->getPrefix()],
            ['type', ($this->type ?? 'cache')],
        ];
    }

    /**
     * @param mixed    $value
     * @param int|null $seconds
     *
     * @return array[]
     */
    protected function makeAttributeColumns($value, ?int $seconds = null): array
    {
        $columns = [];

        $columns[] = ['created_at', Carbon::now()->toRfc3339String(true), ColumnTypeConst::CONST_STRING];

        if (null !== $value) {
            $columns[] = ['value', $this->serialize($value), ColumnTypeConst::CONST_BINARY];
        }

        if (null !== $seconds) {
            $columns[] = ['expiration', $this->availableAt($seconds), ColumnTypeConst::CONST_INTEGER];
        }

        if (null !== $this->owner) {
            $columns[] = ['owner', $this->owner, ColumnTypeConst::CONST_STRING];
        }

        return $columns;
    }

    /**
     * @param array $response
     *
     * @return mixed|null
     */
    protected function parseValueInOtsResponse(array $response)
    {
        $columns = $this->getOts()->parseRowColumns($response);

        if (!isset($columns['value'])) {
            return null;
        }

        if (isset($columns['expiration']) && $this->currentTime() >= $columns['expiration']) {
            return null;
        }

        return $this->unserialize($columns['value']);
    }

    /**
     * @param array $response
     *
     * @return string|null
     */
    protected function parseKeyInOtsResponse(array $response): ?string
    {
        return $this->getOts()->parseRowColumns($response)['key'] ?? null;
    }

    /**
     * Serialize the value.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function serialize($value): string
    {
        if (is_numeric($value) && !in_array($value, [INF, -INF]) && !is_nan($value)) {
            return is_string($value) ? $value : strval($value);
        }

        return serialize($value);
    }

    /**
     * Unserialize the value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function unserialize($value)
    {
        return is_numeric($value) ? $value : unserialize($value);
    }
}
