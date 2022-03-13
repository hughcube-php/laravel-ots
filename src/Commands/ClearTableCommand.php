<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/22
 * Time: 11:18.
 */

namespace HughCube\Laravel\OTS\Commands;

use Aliyun\OTS\Consts\DirectionConst;
use Aliyun\OTS\Consts\OperationTypeConst;
use Aliyun\OTS\Consts\PrimaryKeyTypeConst;
use Aliyun\OTS\Consts\RowExistenceExpectationConst;
use Aliyun\OTS\OTSClientException;
use Aliyun\OTS\OTSServerException;
use Carbon\Carbon;
use HughCube\Laravel\OTS\Connection;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ClearTableCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected $signature = 'ots:clear-table
                            {--ots=ots : ots connection. }
                            {--name= : ots table name. }';

    /**
     * @inheritdoc
     */
    protected $description = 'Clear the ots table data.';

    /**
     * @param Schedule $schedule
     *
     * @throws OTSClientException
     * @throws OTSServerException
     *
     * @return void
     */
    public function handle(Schedule $schedule)
    {
        $tableName = $this->getTable();

        try {
            $table = $this->getOts()->describeTable(['table_name' => $tableName]);
        } catch (OTSServerException $exception) {
            $this->error(sprintf('RequestId: %s', $exception->getRequestId()));
            $this->error(sprintf('%s: %s', $exception->getOTSErrorCode(), $exception->getOTSErrorMessage()));

            return;
        }

        if (!$this->confirm(sprintf('Are you sure you want to clear the "%s" table?', $tableName))) {
            return;
        }

        /** @var array $pks 表主键 */
        $pks = Arr::get($table, 'table_meta.primary_key_schema');

        /** 起始主键 */
        list($startPk, $endPk) = $this->parseRangePk($pks);

        $rowCount = 0;
        while (!empty($startPk)) {
            $request = [
                'table_name'                  => $tableName, 'max_versions' => 1,
                'direction'                   => DirectionConst::CONST_FORWARD,
                'inclusive_start_primary_key' => $startPk,
                'exclusive_end_primary_key'   => $endPk,
                'limit'                       => 200,
            ];
            $response = $this->getOts()->getRange($request);

            /** 删除查询出来的数据 */
            if (!empty($rows = $this->parseDeleteRows($response))) {
                $this->getOts()->batchWriteRow([
                    'tables' => [['table_name' => $tableName, 'rows' => $rows]],
                ]);
            }

            /** 下一次轮询的key */
            $startPk = $response['next_start_primary_key'];

            $rowCount += count($rows);
            $this->comment(sprintf(
                '%s Delete %s rows from the "%s" table.',
                Carbon::now()->format('Y-m-d H:i:s.u'),
                count($rows),
                $tableName
            ));
        }

        $this->info(sprintf(
            '%s The "%s" table has been cleared and %s data items have been deleted.',
            Carbon::now()->format('Y-m-d H:i:s.u'),
            $tableName,
            $rowCount
        ));
    }

    protected function parseRangePk($pks): array
    {
        /** 起始主键 */
        $endPk = $startPk = [];
        foreach ($pks as $pk) {
            $startPk[] = [$pk[0], null, PrimaryKeyTypeConst::CONST_INF_MIN];
            $endPk[] = [$pk[0], null, PrimaryKeyTypeConst::CONST_INF_MAX];
        }

        return [$startPk, $endPk];
    }

    protected function parseDeleteRows($response): array
    {
        $rows = [];
        foreach ($response['rows'] as $row) {
            $rows[] = [
                'operation_type' => OperationTypeConst::CONST_DELETE,
                'condition'      => RowExistenceExpectationConst::CONST_IGNORE,
                'primary_key'    => $row['primary_key'],
            ];
        }

        return $rows;
    }

    /**
     * @return Connection
     */
    public function getOts(): Connection
    {
        return DB::connection($this->option('ots'));
    }

    /**
     * @throws OTSClientException
     * @throws OTSServerException
     *
     * @return string
     */
    protected function getTable(): string
    {
        $name = $this->option('name');
        if (!empty($name)) {
            return $name;
        }

        $tables = $this->getOts()->listTable([]);

        return $this->choice('You want to erase the data from that table?', $tables);
    }
}
