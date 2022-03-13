<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/9
 * Time: 7:25 下午.
 */

namespace HughCube\Laravel\OTS\Commands;

use Aliyun\OTS\OTSClientException;
use Aliyun\OTS\OTSServerException;
use Carbon\Carbon;
use HughCube\Laravel\OTS\Cache\Store;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

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
     * @throws OTSClientException
     * @throws OTSServerException
     *
     * @return void
     */
    public function handle()
    {
        $store = $this->getCache()->getStore();
        if (!$store instanceof Store) {
            $this->warn('Only OTS cache can be processed.');

            return;
        }

        $count = 0;
        $page = 200;
        while (true) {
            $deletedCount = $store->flushExpiredRows($page, $this->getExpiredDuration());
            $this->comment(sprintf(
                '%s Delete %s rows from the "%s" table, The total number of deleted rows is %s.',
                Carbon::now()->format('Y-m-d H:i:s.u'),
                $deletedCount,
                $store->getTable(),
                ($count += $deletedCount)
            ));
            if (0 >= $deletedCount || $deletedCount < $page) {
                break;
            }
        }
    }

    protected function getCache(): Repository
    {
        return Cache::store(($this->option('cache') ?: null));
    }

    protected function getExpiredDuration(): int
    {
        return intval($this->option('expired_duration'));
    }
}
