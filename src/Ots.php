<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/8
 * Time: 5:47 下午.
 */

namespace HughCube\Laravel\OTS;

use Exception;
use Illuminate\Support\Facades\DB;

class Ots
{
    /**
     * @param  string  $name
     * @return Connection
     * @throws
     * @phpstan-ignore-next-line
     */
    public static function connection(string $name = 'ots'): Connection
    {
        $connection = DB::connection($name);

        if (!$connection instanceof Connection) {
            throw new Exception('Only ots connections can be obtained');
        }

        return $connection;
    }

    /**
     * @param  mixed  $row
     * @return array
     */
    public static function parseRow($row): array
    {
        $row = array_merge(($row['primary_key'] ?? []), ($row['attribute_columns'] ?? []));

        $columns = [];
        foreach ($row as $item) {
            if (isset($item[0], $item[1])) {
                $columns[$item[0]] = $item[1];
            }
        }
        return $columns;
    }

    /**
     * @param  mixed  $row
     * @param  string  $name
     * @return int
     */
    public static function parseRowAutoId($row, string $name = 'id'): ?int
    {
        foreach (($row['primary_key'] ?? []) as $key) {
            if (isset($key[0], $key[1]) && $name === $key[0] && is_int($key[1])) {
                return $key[1];
            }
        }
        return null;
    }

    /**
     * @param  mixed  $response
     * @return bool
     */
    public static function batchWriteResponseIsOk($response): bool
    {
        if (empty($response['tables']) || !is_array($response['tables'])) {
            return false;
        }

        foreach ($response['tables'] as $table) {
            foreach (($table['rows'] ?? []) as $row) {
                if (empty($row['is_ok'])) {
                    return false;
                }
            }
        }

        return true;
    }
}
