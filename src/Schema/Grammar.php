<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/27
 * Time: 16:48
 */

namespace HughCube\Laravel\OTS\Schema;

use Aliyun\OTS\Consts\PrimaryKeyTypeConst;
use Illuminate\Support\Fluent;
use RuntimeException;

class Grammar extends \Illuminate\Database\Schema\Grammars\Grammar
{
    public function getType(Fluent $column): string
    {
        return parent::getType($column);
    }

    /**
     * Create the column definition for a char type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeChar(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for a string type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeString(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for a tiny text type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeTinyText(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for a text type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeText(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for a medium text type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeMediumText(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for a long text type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeLongText(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for a big integer type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeBigInteger(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_INTEGER;
    }

    /**
     * Create the column definition for an integer type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeInteger(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_INTEGER;
    }

    /**
     * Create the column definition for a medium integer type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeMediumInteger(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_INTEGER;
    }

    /**
     * Create the column definition for a tiny integer type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeTinyInteger(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_INTEGER;
    }

    /**
     * Create the column definition for a small integer type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeSmallInteger(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_INTEGER;
    }

    /**
     * Create the column definition for a float type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeFloat(Fluent $column): string
    {
        throw new RuntimeException('Unsupported types.');
    }

    /**
     * Create the column definition for a double type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeDouble(Fluent $column): string
    {
        throw new RuntimeException('Unsupported types.');
    }

    /**
     * Create the column definition for a decimal type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeDecimal(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for a boolean type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeBoolean(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_INTEGER;
    }

    /**
     * Create the column definition for an enumeration type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeEnum(Fluent $column): string
    {
        throw new RuntimeException('Unsupported types.');
    }

    /**
     * Create the column definition for a set enumeration type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeSet(Fluent $column): string
    {
        throw new RuntimeException('Unsupported types.');
    }

    /**
     * Create the column definition for a json type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeJson(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for a jsonb type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeJsonb(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for a date type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeDate(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for a date-time type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeDateTime(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for a date-time (with time zone) type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeDateTimeTz(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for a time type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeTime(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for a time (with time zone) type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeTimeTz(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for a timestamp type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeTimestamp(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for a timestamp (with time zone) type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeTimestampTz(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for a year type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeYear(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_INTEGER;
    }

    /**
     * Create the column definition for a binary type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeBinary(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_BINARY;
    }

    /**
     * Create the column definition for a uuid type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeUuid(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for an IP address type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeIpAddress(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for a MAC address type.
     *
     * @param  Fluent  $column
     * @return string
     */
    protected function typeMacAddress(Fluent $column): string
    {
        return PrimaryKeyTypeConst::CONST_STRING;
    }

    /**
     * Create the column definition for a spatial Geometry type.
     *
     * @param  Fluent  $column
     * @return string
     */
    public function typeGeometry(Fluent $column): string
    {
        throw new RuntimeException('Unsupported types.');
    }

    /**
     * Create the column definition for a spatial Point type.
     *
     * @param  Fluent  $column
     * @return string
     */
    public function typePoint(Fluent $column): string
    {
        throw new RuntimeException('Unsupported types.');
    }

    /**
     * Create the column definition for a spatial LineString type.
     *
     * @param  Fluent  $column
     * @return string
     */
    public function typeLineString(Fluent $column): string
    {
        throw new RuntimeException('Unsupported types.');
    }

    /**
     * Create the column definition for a spatial Polygon type.
     *
     * @param  Fluent  $column
     * @return string
     */
    public function typePolygon(Fluent $column): string
    {
        throw new RuntimeException('Unsupported types.');
    }

    /**
     * Create the column definition for a spatial GeometryCollection type.
     *
     * @param  Fluent  $column
     * @return string
     */
    public function typeGeometryCollection(Fluent $column): string
    {
        throw new RuntimeException('Unsupported types.');
    }

    /**
     * Create the column definition for a spatial MultiPoint type.
     *
     * @param  Fluent  $column
     * @return string
     */
    public function typeMultiPoint(Fluent $column): string
    {
        throw new RuntimeException('Unsupported types.');
    }

    /**
     * Create the column definition for a spatial MultiLineString type.
     *
     * @param  Fluent  $column
     * @return string
     */
    public function typeMultiLineString(Fluent $column): string
    {
        throw new RuntimeException('Unsupported types.');
    }

    /**
     * Create the column definition for a spatial MultiPolygon type.
     *
     * @param  Fluent  $column
     * @return string
     */
    public function typeMultiPolygon(Fluent $column): string
    {
        throw new RuntimeException('Unsupported types.');
    }
}
