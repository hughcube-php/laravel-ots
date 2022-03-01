<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/9
 * Time: 7:25 下午.
 */

namespace HughCube\Laravel\OTS\Commands;

use Aliyun\OTS\Consts\ColumnTypeConst;
use Aliyun\OTS\Consts\ComparatorTypeConst;
use Aliyun\OTS\Consts\DirectionConst;
use Aliyun\OTS\Consts\OperationTypeConst;
use Aliyun\OTS\Consts\PrimaryKeyTypeConst;
use Aliyun\OTS\Consts\RowExistenceExpectationConst;
use Aliyun\OTS\OTSClientException;
use Aliyun\OTS\OTSServerException;
use Carbon\Carbon;
use HughCube\Laravel\OTS\Cache\Store;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class CacheGc extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'ots:gc-cache
                        {--cache=ots : name of cache. }
                        {--expired_duration=2592000 : The data that has expired is cleared. }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ots cache gc';

    /**
     * @return void
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function handle()
    {
        $store = $this->getCache()->getStore();
        if (!$store instanceof Store) {
            $this->warn('Only OTS cache can be processed.');
            return;
        }

        $rowCount = 0;
        list($startPk, $endPk) = $this->parsePkRange();
        while (!empty($startPk)) {
            $request = [
                'table_name' => $store->getIndexTable(),
                'max_versions' => 1,
                'direction' => DirectionConst::CONST_FORWARD,
                'inclusive_start_primary_key' => $startPk,
                'exclusive_end_primary_key' => $endPk,
                'limit' => 200,
            ];
            $response = $store->getOts()->getRange($request);

            /** 删除查询出来的数据 */
            if (!empty($rows = $this->parseDeleteRows($response))) {
                $store->getOts()->batchWriteRow([
                    'tables' => [
                        ['table_name' => $store->getTable(), 'rows' => $rows]
                    ]
                ]);
            }

            /** 下一次轮询的key */
            $startPk = $response['next_start_primary_key'];

            $rowCount += count($rows);
            $this->comment(sprintf(
                '%s Delete %s rows from the "%s" table.',
                Carbon::now()->format('Y-m-d H:i:s.u'),
                count($rows),
                $store->getTable()
            ));
        }
    }

    protected function getCache(): Repository
    {
        return Cache::store(($this->option('cache') ?: null));
    }

    protected function getExpiredDuration(): int
    {
        $duration = intval($this->option('expired_duration'));
        if ($duration < 24 * 3600) {
            throw new InvalidArgumentException('At least one day expired before it can be cleaned.');
        }

        return $duration;
    }

    protected function parsePkRange(): array
    {
        $startPk = [
            ['expiration', 1],
            ['key', null, PrimaryKeyTypeConst::CONST_INF_MIN],
            ['prefix', null, PrimaryKeyTypeConst::CONST_INF_MIN],
            ['type', null, PrimaryKeyTypeConst::CONST_INF_MIN],
        ];

        $endPk = [
            ['expiration', (time() - $this->getExpiredDuration())],
            ['key', null, PrimaryKeyTypeConst::CONST_INF_MAX],
            ['prefix', null, PrimaryKeyTypeConst::CONST_INF_MAX],
            ['type', null, PrimaryKeyTypeConst::CONST_INF_MAX],
        ];

        return [$startPk, $endPk];
    }

    protected function parseDeleteRows($response): array
    {
        $rows = [];
        foreach ($response['rows'] as $row) {
            $row = Collection::make($row['primary_key'])->keyBy(0);
            $rows[] = [
                'operation_type' => OperationTypeConst::CONST_DELETE,
                'condition' => [
                    'row_existence' => RowExistenceExpectationConst::CONST_IGNORE,
                    /** (Col2 <= 10) */
                    'column_condition' => [
                        'column_name' => 'expiration',
                        'value' => [time(), ColumnTypeConst::CONST_INTEGER],
                        'comparator' => ComparatorTypeConst::CONST_LESS_EQUAL,
                        'pass_if_missing' => true,
                        'latest_version_only' => true,
                    ],
                ],
                'primary_key' => [
                    $row->get('key'),
                    $row->get('prefix'),
                    $row->get('type'),
                ],
            ];
        }
        return $rows;
    }
}
