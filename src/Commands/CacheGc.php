<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/9
 * Time: 7:25 下午.
 */

namespace HughCube\Laravel\OTS\Commands;

use Illuminate\Console\Command;

class CacheGc extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'cache:ots-gc
                        {expiredTime=2592000: 已经过期多久的数据开始清理 }
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ots cache gc';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
    }
}
