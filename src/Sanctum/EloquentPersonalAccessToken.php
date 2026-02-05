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
 * Eloquent Model based Personal Access Token for Laravel Sanctum.
 * This implementation extends Sanctum's PersonalAccessToken model.
 *
 * @property string      $tokenable_type
 * @property int         $tokenable_id
 * @property string      $name
 * @property string      $token
 * @property array       $abilities
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class EloquentPersonalAccessToken extends \Laravel\Sanctum\PersonalAccessToken
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
        return config('app.name', 'laravel');
    }

    /**
     * Check if the token is still valid (last used within expiration days).
     *
     * @param int $expirationDays Number of days after last use before token expires
     *
     * @return bool
     */
    public function isValidAccessToken(int $expirationDays = 15): bool
    {
        if (!$this->last_used_at instanceof Carbon) {
            return true;
        }

        return $this->last_used_at->gt(Carbon::now()->subDays($expirationDays));
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     * @throws Exception
     */
    public static function findToken($token): ?EloquentPersonalAccessToken
    {
        $request = [
            'table_name'  => static::getOtsTable(),
            'primary_key' => [
                ['token', hash('sha256', $token)],
                ['app', static::getApp()],
            ],
            'max_versions' => 1,
        ];

        try {
            $row = static::getOts()->getRow($request);
        } catch (OTSServerException $e) {
            if (404 === $e->getHttpStatus()) {
                return null;
            }
            throw $e;
        }

        if (empty($row['row'])) {
            return null;
        }

        /** @var static $model */
        $model = static::query()->newModelInstance();
        $model->forceFill(static::parseOtsRow($row));
        $model->exists = true;

        return $model;
    }

    /**
     * Parse OTS row response to model attributes.
     *
     * @return array
     */
    protected static function parseOtsRow(array $response): array
    {
        $attributes = [];

        $row = $response['row'] ?? $response;

        // Parse primary keys
        foreach ($row['primary_key'] ?? [] as $pk) {
            $attributes[$pk[0]] = $pk[1];
        }

        // Parse attribute columns
        foreach ($row['attribute_columns'] ?? [] as $col) {
            $key = $col[0];
            $value = $col[1];

            // Convert timestamps to Carbon
            if (in_array($key, ['last_used_at', 'created_at', 'updated_at']) && is_numeric($value)) {
                $value = Carbon::createFromTimestamp($value);
            }

            // Decode abilities JSON
            if ($key === 'abilities' && is_string($value)) {
                $decoded = json_decode($value, true);
                $value = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
            }

            $attributes[$key] = $value;
        }

        return $attributes;
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     * @throws Exception
     */
    public function save(array $options = []): bool
    {
        $this->created_at = $this->created_at ?? Carbon::now();
        $this->updated_at = Carbon::now();

        if (!$this->exists) {
            return $this->saveInsert();
        }

        return $this->saveUpdate();
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     * @throws Exception
     */
    protected function saveInsert(): bool
    {
        $this->last_used_at = $this->last_used_at ?? Carbon::now();

        $request = [
            'table_name'  => static::getOtsTable(),
            'condition'   => RowExistenceExpectationConst::CONST_EXPECT_NOT_EXIST,
            'primary_key' => [
                ['token', $this->token],
                ['app', static::getApp()],
            ],
            'attribute_columns' => [
                ['tokenable_type', $this->tokenable_type, ColumnTypeConst::CONST_STRING],
                ['tokenable_id', Base::digitalToString($this->tokenable_id), ColumnTypeConst::CONST_STRING],
                ['name', $this->name, ColumnTypeConst::CONST_STRING],
                ['abilities', json_encode($this->abilities), ColumnTypeConst::CONST_STRING],
                ['last_used_at', $this->last_used_at->getTimestamp(), ColumnTypeConst::CONST_INTEGER],
                ['created_at', $this->created_at->getTimestamp(), ColumnTypeConst::CONST_INTEGER],
                ['updated_at', $this->updated_at->getTimestamp(), ColumnTypeConst::CONST_INTEGER],
            ],
        ];

        static::getOts()->putRow($request);
        $this->exists = true;

        return true;
    }

    /**
     * @throws OTSServerException
     * @throws OTSClientException
     * @throws Exception
     */
    protected function saveUpdate(): bool
    {
        $request = [
            'table_name'  => static::getOtsTable(),
            'condition'   => RowExistenceExpectationConst::CONST_EXPECT_EXIST,
            'primary_key' => [
                ['token', $this->token],
                ['app', static::getApp()],
            ],
            'update_of_attribute_columns' => [
                'PUT' => [
                    ['tokenable_type', $this->tokenable_type, ColumnTypeConst::CONST_STRING],
                    ['tokenable_id', Base::digitalToString($this->tokenable_id), ColumnTypeConst::CONST_STRING],
                    ['name', $this->name, ColumnTypeConst::CONST_STRING],
                    ['abilities', json_encode($this->abilities), ColumnTypeConst::CONST_STRING],
                    ['last_used_at', $this->last_used_at->getTimestamp(), ColumnTypeConst::CONST_INTEGER],
                    ['created_at', $this->created_at->getTimestamp(), ColumnTypeConst::CONST_INTEGER],
                    ['updated_at', $this->updated_at->getTimestamp(), ColumnTypeConst::CONST_INTEGER],
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
    public function delete(): ?bool
    {
        $request = [
            'table_name'  => static::getOtsTable(),
            'condition'   => RowExistenceExpectationConst::CONST_IGNORE,
            'primary_key' => [
                ['token', $this->token],
                ['app', static::getApp()],
            ],
        ];

        static::getOts()->deleteRow($request);
        $this->exists = false;

        return true;
    }
}
