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
use HughCube\Laravel\OTS\Connection;
use HughCube\Laravel\OTS\Ots;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Laravel\Sanctum\Contracts\HasAbilities;

/**
 * OTS-based Personal Access Token for Laravel Sanctum.
 * This implementation does not use Eloquent Model, directly queries OTS table.
 */
class PersonalAccessToken implements HasAbilities, Arrayable, Jsonable, JsonSerializable
{
    /**
     * The token's attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The tokenable model instance.
     *
     * @var mixed
     */
    protected $tokenableInstance = null;

    /**
     * Whether the token exists in storage.
     *
     * @var bool
     */
    public $exists = false;

    /**
     * The plain text token (only available after creation).
     *
     * @var string|null
     */
    public $plainTextToken = null;

    /**
     * The OTS connection name.
     *
     * @var string|null
     */
    protected static $connectionName = null;

    /**
     * The table name.
     *
     * @var string
     */
    protected static $tableName = 'personal_access_tokens';

    /**
     * Create a new PersonalAccessToken instance.
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Get the OTS connection.
     *
     * @throws Exception
     */
    public static function getOts(): Connection
    {
        return Ots::connection(static::$connectionName);
    }

    /**
     * Set the OTS connection name.
     */
    public static function setConnectionName(?string $name): void
    {
        static::$connectionName = $name;
    }

    /**
     * Get the table name.
     */
    public static function getOtsTable(): string
    {
        return static::$tableName;
    }

    /**
     * Set the table name.
     */
    public static function setTableName(string $name): void
    {
        static::$tableName = $name;
    }

    /**
     * Get the app name for partitioning.
     */
    public static function getApp(): string
    {
        return config('app.name', 'laravel');
    }

    /**
     * Fill the token with attributes.
     *
     * @return $this
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Set an attribute.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function setAttribute(string $key, $value)
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Get an attribute.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Magic getter.
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter.
     *
     * @param mixed $value
     */
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Magic isset.
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Determine if the token has a given ability.
     *
     * @param string $ability
     *
     * @return bool
     */
    public function can($ability)
    {
        $abilities = $this->getAttribute('abilities', []);

        return in_array('*', $abilities, true)
            || in_array($ability, $abilities, true);
    }

    /**
     * Determine if the token is missing a given ability.
     *
     * @param string $ability
     *
     * @return bool
     */
    public function cant($ability)
    {
        return !$this->can($ability);
    }

    /**
     * Get the tokenable model instance.
     *
     * @return mixed
     */
    public function tokenable()
    {
        if ($this->tokenableInstance !== null) {
            return $this->tokenableInstance;
        }

        $tokenableType = $this->getAttribute('tokenable_type');
        $tokenableId = $this->getAttribute('tokenable_id');

        if ($tokenableType && $tokenableId && class_exists($tokenableType)) {
            $this->tokenableInstance = $tokenableType::find($tokenableId);
        }

        return $this->tokenableInstance;
    }

    /**
     * Check if the token is still valid.
     *
     * @param int $expirationDays Number of days after last use before token expires
     *
     * @return bool
     */
    public function isValidAccessToken(int $expirationDays = 15): bool
    {
        $lastUsedAt = $this->getAttribute('last_used_at');

        if (!$lastUsedAt instanceof Carbon) {
            return true;
        }

        return $lastUsedAt->gt(Carbon::now()->subDays($expirationDays));
    }

    /**
     * Find a token by its plain text value.
     *
     * @param string $token
     *
     * @return static|null
     *
     * @throws OTSServerException
     * @throws OTSClientException
     * @throws Exception
     */
    public static function findToken($token)
    {
        $hashedToken = hash('sha256', $token);

        $request = [
            'table_name' => static::getOtsTable(),
            'primary_key' => [
                ['token', $hashedToken],
                ['app', static::getApp()],
            ],
            'max_versions' => 1,
        ];

        try {
            $response = static::getOts()->getRow($request);
        } catch (OTSServerException $e) {
            if (404 === $e->getHttpStatus()) {
                return null;
            }
            throw $e;
        }

        if (empty($response['row'])) {
            return null;
        }

        $attributes = static::parseRow($response);
        if (empty($attributes)) {
            return null;
        }

        $instance = new static($attributes);
        $instance->exists = true;

        return $instance;
    }

    /**
     * Create a new token.
     *
     * @param mixed  $tokenable  The model that owns the token
     * @param string $name       The token name
     * @param array  $abilities  The token abilities
     *
     * @return static
     *
     * @throws OTSServerException
     * @throws OTSClientException
     * @throws Exception
     */
    public static function createToken($tokenable, string $name, array $abilities = ['*'])
    {
        $plainTextToken = bin2hex(random_bytes(20));

        $instance = new static([
            'token' => hash('sha256', $plainTextToken),
            'tokenable_type' => get_class($tokenable),
            'tokenable_id' => $tokenable->getKey(),
            'name' => $name,
            'abilities' => $abilities,
            'last_used_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $instance->save();
        $instance->plainTextToken = $plainTextToken;

        return $instance;
    }

    /**
     * Save the token.
     *
     * @return bool
     *
     * @throws OTSServerException
     * @throws OTSClientException
     * @throws Exception
     */
    public function save(): bool
    {
        $now = Carbon::now();
        $this->setAttribute('updated_at', $now);

        if (!$this->exists) {
            $this->setAttribute('created_at', $this->getAttribute('created_at', $now));
            $this->setAttribute('last_used_at', $this->getAttribute('last_used_at', $now));

            return $this->insertRow();
        }

        return $this->updateRow();
    }

    /**
     * Insert a new token.
     *
     * @return bool
     *
     * @throws OTSServerException
     * @throws OTSClientException
     * @throws Exception
     */
    protected function insertRow(): bool
    {
        $request = [
            'table_name' => static::getOtsTable(),
            'condition' => RowExistenceExpectationConst::CONST_EXPECT_NOT_EXIST,
            'primary_key' => [
                ['token', $this->getAttribute('token')],
                ['app', static::getApp()],
            ],
            'attribute_columns' => $this->buildAttributeColumns(),
        ];

        static::getOts()->putRow($request);
        $this->exists = true;

        return true;
    }

    /**
     * Update the token.
     *
     * @return bool
     *
     * @throws OTSServerException
     * @throws OTSClientException
     * @throws Exception
     */
    protected function updateRow(): bool
    {
        $request = [
            'table_name' => static::getOtsTable(),
            'condition' => RowExistenceExpectationConst::CONST_EXPECT_EXIST,
            'primary_key' => [
                ['token', $this->getAttribute('token')],
                ['app', static::getApp()],
            ],
            'update_of_attribute_columns' => [
                'PUT' => $this->buildAttributeColumns(),
            ],
        ];

        static::getOts()->updateRow($request);

        return true;
    }

    /**
     * Delete the token.
     *
     * @return bool
     *
     * @throws OTSServerException
     * @throws OTSClientException
     * @throws Exception
     */
    public function delete(): bool
    {
        $request = [
            'table_name' => static::getOtsTable(),
            'condition' => RowExistenceExpectationConst::CONST_IGNORE,
            'primary_key' => [
                ['token', $this->getAttribute('token')],
                ['app', static::getApp()],
            ],
        ];

        static::getOts()->deleteRow($request);
        $this->exists = false;

        return true;
    }

    /**
     * Build attribute columns for OTS request.
     *
     * @return array
     */
    protected function buildAttributeColumns(): array
    {
        $abilities = $this->getAttribute('abilities', []);
        $abilitiesJson = is_array($abilities) ? json_encode($abilities) : $abilities;

        $lastUsedAt = $this->getAttribute('last_used_at');
        $createdAt = $this->getAttribute('created_at');
        $updatedAt = $this->getAttribute('updated_at');

        return [
            ['tokenable_type', (string) $this->getAttribute('tokenable_type'), ColumnTypeConst::CONST_STRING],
            ['tokenable_id', (string) $this->getAttribute('tokenable_id'), ColumnTypeConst::CONST_STRING],
            ['name', (string) $this->getAttribute('name'), ColumnTypeConst::CONST_STRING],
            ['abilities', $abilitiesJson, ColumnTypeConst::CONST_STRING],
            ['last_used_at', $lastUsedAt instanceof Carbon ? $lastUsedAt->getTimestamp() : (int) $lastUsedAt, ColumnTypeConst::CONST_INTEGER],
            ['created_at', $createdAt instanceof Carbon ? $createdAt->getTimestamp() : (int) $createdAt, ColumnTypeConst::CONST_INTEGER],
            ['updated_at', $updatedAt instanceof Carbon ? $updatedAt->getTimestamp() : (int) $updatedAt, ColumnTypeConst::CONST_INTEGER],
        ];
    }

    /**
     * Parse OTS row response to attributes.
     *
     * @return array
     */
    protected static function parseRow(array $response): array
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
     * Convert the token to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Convert the token to JSON.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the token to a JSON serializable array.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
