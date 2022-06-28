<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/14
 * Time: 17:41.
 */

namespace HughCube\Laravel\OTS\Sanctum;

use Aliyun\OTS\Consts\ColumnTypeConst;
use Aliyun\OTS\Consts\RowExistenceExpectationConst;
use Aliyun\OTS\OTSClientException;
use Aliyun\OTS\OTSServerException;
use Carbon\Carbon;
use Exception;
use HughCube\Base\Base;
use HughCube\Laravel\OTS\Connection;
use HughCube\Laravel\OTS\Ots;

/**
 * @property string $tokenable_type
 * @property int $tokenable_id
 * @property string $name
 * @property string $token
 * @property array $abilities
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PersonalAccessToken extends \Laravel\Sanctum\PersonalAccessToken
{
    /**
     * @throws Exception
     */
    protected static function getOts(): Connection
    {
        return Ots::connection();
    }

    public static function getOtsTable(): string
    {
        return 'personal_access_tokens';
    }

    public static function getApp(): string
    {
        return config('app.name');
    }

    /**
     * 最后一次使用时间在15天之前.
     *
     * @param  bool  $isValid  验证的当前值
     *
     * @return bool
     */
    public function isValidAccessToken(bool $isValid = true): bool
    {
        if (!$this->last_used_at instanceof Carbon) {
            return true;
        }

        return $this->last_used_at->gt(Carbon::now()->subSeconds(3600 * 24 * 15));
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     * @throws Exception
     */
    public static function findToken($token): ?PersonalAccessToken
    {
        $request = [
            'table_name' => static::getOtsTable(),
            'primary_key' => [
                ['token', hash('sha256', $token)],
                ['app', static::getApp()],
            ],
            'max_versions' => 1,
        ];

        $row = Ots::parseRow(static::getOts()->getRow($request));

        /** @var static $model */
        $model = static::query()->newModelInstance();
        $model->forceFill($row);
        $model->exists = true;

        /** @phpstan-ignore-next-line */
        if (is_string($model->abilities)) {
            $abilities = json_decode($model->abilities, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                $abilities = [];
            }
            $model->abilities = $abilities;
        }

        return $model;
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     * @throws Exception
     */
    public function save(array $options = []): bool
    {
        $this->created_at = $this->created_at ?? Carbon::now();
        $this->updated_at = $this->updated_at ?? Carbon::now();

        if (!$this->exists) {
            return $this->saveInstall();
        }

        $this->updated_at = Carbon::now();
        $request = [
            'table_name' => $this->table,
            'condition' => RowExistenceExpectationConst::CONST_EXPECT_EXIST,
            'primary_key' => [
                ['token', $this->token],
                ['app', $this->getApp()],
            ],
            'update_of_attribute_columns' => [
                'PUT' => [
                    ['tokenable_type', $this->tokenable_type, ColumnTypeConst::CONST_STRING],
                    ['tokenable_id', Base::digitalToString($this->tokenable_id), ColumnTypeConst::CONST_STRING],
                    ['name', $this->name, ColumnTypeConst::CONST_STRING],
                    ['abilities', json_encode($this->abilities), ColumnTypeConst::CONST_STRING],
                    ['last_used_at', $this->last_used_at->getTimestamp(), ColumnTypeConst::CONST_DOUBLE],
                    ['created_at', $this->created_at->getTimestamp(), ColumnTypeConst::CONST_DOUBLE],
                    ['updated_at', $this->updated_at->getTimestamp(), ColumnTypeConst::CONST_DOUBLE],
                ],
            ],
        ];

        static::getOts()->updateRow($request);

        return true;
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     * @throws Exception
     */
    protected function saveInstall(): bool
    {
        $this->last_used_at = $this->last_used_at ?? Carbon::now();

        $request = [
            'table_name' => $this->table,
            'condition' => RowExistenceExpectationConst::CONST_EXPECT_NOT_EXIST,
            'primary_key' => [
                ['token', $this->token],
                ['app', $this->getApp()],
            ],
            'attribute_columns' => [
                ['tokenable_type', $this->tokenable_type, ColumnTypeConst::CONST_STRING],
                ['tokenable_id', Base::digitalToString($this->tokenable_id), ColumnTypeConst::CONST_STRING],
                ['name', $this->name, ColumnTypeConst::CONST_STRING],
                ['abilities', json_encode($this->abilities), ColumnTypeConst::CONST_STRING],
                ['last_used_at', $this->last_used_at->getTimestamp(), ColumnTypeConst::CONST_DOUBLE],
                ['created_at', $this->created_at->getTimestamp(), ColumnTypeConst::CONST_DOUBLE],
                ['updated_at', $this->updated_at->getTimestamp(), ColumnTypeConst::CONST_DOUBLE],
            ],
        ];

        static::getOts()->putRow($request);
        $this->exists = true;

        return true;
    }
}
