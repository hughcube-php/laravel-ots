<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/8
 * Time: 11:02 下午
 */

namespace HughCube\Laravel\OTS\Cache;


use Aliyun\OTS\Consts\ColumnTypeConst;
use Aliyun\OTS\OTSClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\InteractsWithTime;

trait Attribute
{
    use InteractsWithTime;

    /**
     * @var OTSClient
     */
    protected $ots;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var bool
     */
    protected $type = 'cache';

    /**
     * @var string
     */
    protected $owner;

    /**
     * @param $ots
     * @return $this
     */
    public function setOts($ots)
    {
        $this->ots = $ots;
        return $this;
    }

    /**
     * @param $ots
     * @return $this
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @param $ots
     * @return $this
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string $key
     * @return array
     */
    protected function makePrimaryKey($key)
    {
        return [
            ['key', $key],
            ['prefix', $this->getPrefix()],
            ['type', ($this->type ?? 'cache')]
        ];
    }

    /**
     * @param $value
     * @param $seconds
     * @return array[]
     */
    protected function makeAttributeColumns($value, $seconds = null)
    {
        $columns = [
            ['created_at', Carbon::now()->toRfc3339String(true), ColumnTypeConst::CONST_STRING]
        ];

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
     * @param $response
     * @return mixed
     */
    protected function parseValueInOtsResponse($response)
    {
        if (empty($response['attribute_columns'])) {
            return null;
        }

        $columns = [];
        foreach ($response['attribute_columns'] as $attribute) {
            $columns[$attribute[0]] = $attribute[1];
        }

        if (!isset($columns['value']) || is_null($columns['value'])) {
            return null;
        }

        if (isset($columns['expiration']) && $this->currentTime() >= $columns['expiration']) {
            return null;
        }

        return $this->unserialize($columns['value']);
    }

    /**
     * @param $response
     * @return string|null
     */
    protected function parseKeyInOtsResponse($response)
    {
        if (empty($response['primary_key'])) {
            return null;
        }

        foreach ($response['primary_key'] as $primaryKey) {
            if ("key" === $primaryKey[0]) {
                return $primaryKey[1];
            }
        }

        return null;
    }

    /**
     * Serialize the value.
     *
     * @param mixed $value
     * @return string
     */
    protected function serialize($value)
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
     * @return mixed
     */
    protected function unserialize($value)
    {
        return is_numeric($value) ? $value : unserialize($value);
    }
}
