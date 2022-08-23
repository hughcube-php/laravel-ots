<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/8/23
 * Time: 15:58
 */

namespace HughCube\Laravel\OTS\Tests\Ots;

use Aliyun\OTS\Consts\ColumnReturnTypeConst;
use Aliyun\OTS\Consts\QueryTypeConst;
use Aliyun\OTS\OTSClientException;
use HughCube\Laravel\OTS\Tests\TestCase;

class AsyncSearchTest extends TestCase
{
    /**
     * @throws OTSClientException
     */
    public function testAsyncSearch()
    {
        $context = $this->getConnection()->asyncSearch([
            'table_name' => 'cache',
            'index_name' => 'cache_index',
            'search_query' => [
                'offset' => 0,
                'limit' => 10,
                'get_total_count' => true,
                'collapse' => ['field_name' => 'key'],
                'query' => ['query_type' => QueryTypeConst::MATCH_ALL_QUERY],
                'token' => null,
            ],
            'columns_to_get' => [
                'return_type' => ColumnReturnTypeConst::RETURN_SPECIFIED,
                'return_names' => ['prefix', 'type', 'key']
            ]
        ]);

        $response = $context->HWait();

        $this->assertIsArray($response);
        $this->assertIsInt($response['total_hits']);
    }
}
