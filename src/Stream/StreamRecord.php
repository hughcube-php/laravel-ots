<?php

namespace HughCube\Laravel\OTS\Stream;

class StreamRecord
{
    public const ACTION_PUT = 'PUT_ROW';
    public const ACTION_UPDATE = 'UPDATE_ROW';
    public const ACTION_DELETE = 'DELETE_ROW';

    /**
     * @var array
     */
    protected $raw;

    /**
     * @var string
     */
    protected $actionType;

    /**
     * @var array
     */
    protected $primaryKey = [];

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @param array $record
     */
    public function __construct(array $record)
    {
        $this->raw = $record;
        $this->actionType = $record['action_type'] ?? '';
        $this->parsePrimaryKey($record['primary_key'] ?? []);
        $this->parseAttributes($record['attribute_columns'] ?? []);
    }

    /**
     * Get the raw record data.
     *
     * @return array
     */
    public function getRaw()
    {
        return $this->raw;
    }

    /**
     * Get the action type (PUT_ROW, UPDATE_ROW, DELETE_ROW).
     *
     * @return string
     */
    public function getActionType()
    {
        return $this->actionType;
    }

    /**
     * Check if this is a PUT action.
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->actionType === self::ACTION_PUT;
    }

    /**
     * Check if this is an UPDATE action.
     *
     * @return bool
     */
    public function isUpdate()
    {
        return $this->actionType === self::ACTION_UPDATE;
    }

    /**
     * Check if this is a DELETE action.
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->actionType === self::ACTION_DELETE;
    }

    /**
     * Get the primary key values.
     *
     * @return array
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Get a specific primary key value.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getPrimaryKeyValue($name)
    {
        return $this->primaryKey[$name] ?? null;
    }

    /**
     * Get the attribute columns.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Get a specific attribute value.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getAttribute($name)
    {
        return $this->attributes[$name]['value'] ?? null;
    }

    /**
     * Get attribute with metadata (value, timestamp, type).
     *
     * @param string $name
     *
     * @return array|null
     */
    public function getAttributeWithMeta($name)
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Get all data as a flat array (primary key + attributes).
     *
     * @return array
     */
    public function toArray()
    {
        $data = $this->primaryKey;

        foreach ($this->attributes as $name => $attr) {
            $data[$name] = $attr['value'];
        }

        return $data;
    }

    /**
     * Parse primary key from the record.
     *
     * @param array $primaryKey
     *
     * @return void
     */
    protected function parsePrimaryKey(array $primaryKey)
    {
        foreach ($primaryKey as $pk) {
            if (isset($pk[0], $pk[1])) {
                $this->primaryKey[$pk[0]] = $pk[1];
            }
        }
    }

    /**
     * Parse attribute columns from the record.
     *
     * @param array $attributes
     *
     * @return void
     */
    protected function parseAttributes(array $attributes)
    {
        foreach ($attributes as $attr) {
            $name = $attr[0] ?? null;
            if ($name === null) {
                continue;
            }

            $this->attributes[$name] = [
                'value' => $attr[1] ?? null,
                'type' => $attr[2] ?? null,
                'timestamp' => $attr[3] ?? null,
            ];
        }
    }
}
