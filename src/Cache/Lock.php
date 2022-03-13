<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/8
 * Time: 7:42 下午.
 */

namespace HughCube\Laravel\OTS\Cache;

use Aliyun\OTS\Consts\ColumnTypeConst;
use Aliyun\OTS\Consts\ComparatorTypeConst;
use Aliyun\OTS\Consts\LogicalOperatorConst;
use Aliyun\OTS\Consts\RowExistenceExpectationConst;
use Aliyun\OTS\OTSClientException;
use Aliyun\OTS\OTSServerException;
use HughCube\Laravel\OTS\Connection;

class Lock extends \Illuminate\Cache\Lock
{
    use Attribute;

    /**
     * Lock constructor.
     *
     * @param Connection  $ots
     * @param string      $table
     * @param string      $prefix
     * @param string      $name
     * @param int         $seconds
     * @param string|null $owner
     */
    public function __construct($ots, $table, $prefix, string $name, int $seconds, ?string $owner = null)
    {
        parent::__construct($name, $seconds, $owner);

        $this->ots = $ots;
        $this->table = $table;
        $this->prefix = $prefix;
        $this->type = 'lock';
    }

    /**
     * Attempt to acquire the lock.
     *
     * @throws OTSServerException
     * @throws OTSClientException
     *
     * @return bool
     */
    public function acquire(): bool
    {
        $request = [
            'table_name' => $this->getTable(),
            'condition'  => [
                'row_existence'    => RowExistenceExpectationConst::CONST_IGNORE,
                'column_condition' => [
                    'logical_operator' => LogicalOperatorConst::CONST_OR,
                    'sub_conditions'   => [
                        /** (`owner` != $this->owner OR `owner` IS NULL) AND (`expiration` >=  time()) */
                        [
                            'logical_operator' => LogicalOperatorConst::CONST_AND,
                            'sub_conditions'   => [
                                [
                                    'column_name'         => 'owner',
                                    'value'               => [$this->owner, ColumnTypeConst::CONST_STRING],
                                    'comparator'          => ComparatorTypeConst::CONST_NOT_EQUAL,
                                    'pass_if_missing'     => true,
                                    'latest_version_only' => true,
                                ],
                                [
                                    'column_name'         => 'expiration',
                                    'value'               => [$this->currentTime(), ColumnTypeConst::CONST_INTEGER],
                                    'comparator'          => ComparatorTypeConst::CONST_LESS_EQUAL,
                                    'pass_if_missing'     => true,
                                    'latest_version_only' => true,
                                ],
                            ],
                        ],

                        /** (`owner` = $this->owner OR `owner` IS NULL) */
                        [

                            'column_name'         => 'owner',
                            'value'               => [$this->owner, ColumnTypeConst::CONST_STRING],
                            'comparator'          => ComparatorTypeConst::CONST_EQUAL,
                            'pass_if_missing'     => true,
                            'latest_version_only' => true,
                        ],
                    ],
                ],
            ],
            'primary_key'       => $this->makePrimaryKey($this->name),
            'attribute_columns' => $this->makeAttributeColumns(time(), $this->seconds),
        ];

        try {
            $response = $this->getOts()->putRow($request);

            return isset($response['primary_key'], $response['attribute_columns']);
        } catch (OTSServerException $exception) {
            if ('OTSConditionCheckFail' === $exception->getOTSErrorCode()) {
                return false;
            }

            throw $exception;
        }
    }

    /**
     * Release the lock.
     *
     * @throws OTSClientException
     * @throws OTSServerException
     *
     * @return bool
     */
    public function release(): bool
    {
        $request = [
            'table_name' => $this->getTable(),
            'condition'  => [
                'row_existence' => RowExistenceExpectationConst::CONST_EXPECT_EXIST,
                /** (`owner` = $this->owner) */
                'column_condition' => [
                    'column_name'         => 'owner',
                    'value'               => $this->owner,
                    'comparator'          => ComparatorTypeConst::CONST_EQUAL,
                    'pass_if_missing'     => false,
                    'latest_version_only' => true,
                ],
            ],
            'primary_key' => $this->makePrimaryKey($this->name),
        ];

        try {
            $response = $this->getOts()->deleteRow($request);

            return isset($response['primary_key'], $response['attribute_columns']);
        } catch (OTSServerException $exception) {
            if ('OTSConditionCheckFail' === $exception->getOTSErrorCode()) {
                return false;
            }

            throw $exception;
        }
    }

    /**
     * Releases this lock in disregard of ownership.
     *
     * @throws OTSClientException
     * @throws OTSServerException
     *
     * @return bool
     */
    public function forceRelease(): bool
    {
        $request = [
            'table_name'  => $this->getTable(),
            'condition'   => RowExistenceExpectationConst::CONST_IGNORE,
            'primary_key' => $this->makePrimaryKey($this->name),
        ];

        $response = $this->getOts()->deleteRow($request);

        return isset($response['primary_key'], $response['attribute_columns']);
    }

    /**
     * Returns the owner value written into the driver for this lock.
     *
     * @throws OTSClientException
     * @throws OTSServerException
     *
     * @return mixed|string
     */
    protected function getCurrentOwner()
    {
        $request = [
            'table_name'   => $this->getTable(),
            'primary_key'  => $this->makePrimaryKey($this->name),
            'max_versions' => 1,
        ];

        $response = $this->getOts()->getRow($request);

        if (null === $this->parseValueInOtsResponse($response)) {
            return '';
        }

        foreach ($response['attribute_columns'] as $attribute) {
            if ('owner' === $attribute[0]) {
                return $attribute[1];
            }
        }

        return '';
    }
}
