<?php

namespace HughCube\Laravel\OTS\OTS;

use Aliyun\OTS\Consts\DefinedColumnTypeConst;
use Aliyun\OTS\OTSClient;
use Aliyun\OTS\OTSClientException;
use Aliyun\OTS\OTSServerException;
use HughCube\Laravel\OTS\OTS\Handlers\OTSHandlers;
use HughCube\Laravel\OTS\OTS\Handlers\RequestContext;

/**
 * AddDefinedColumn / DeleteDefinedColumn API 封装。
 *
 * aliyun/aliyun-tablestore-sdk-php 至 v6.0.1 未封装这两个 API，也未生成对应的 protobuf
 * 消息类。本类不新增任何 protobuf 生成类、不改动 vendor，通过手工编码 wire format
 * 组装请求体，再复用 aliyun SDK 已有的 HttpHeaderHandler（签名）/ HttpClient（传输）/
 * ErrorHandler（错误解析）发起请求，保证与 SDK 其他 API 在签名算法、超时、错误语义
 * 上一致。
 *
 * 请求消息结构（与阿里云 Go/Java SDK 一致）：
 *   message AddDefinedColumnRequest    { required string table_name=1; repeated DefinedColumnSchema columns=2; }
 *   message DeleteDefinedColumnRequest { required string table_name=1; repeated string columns=2; }
 *   message DefinedColumnSchema        { required string name=1; required DefinedColumnType type=2; }
 *   enum DefinedColumnType             { DCT_INTEGER=1; DCT_DOUBLE=2; DCT_BOOLEAN=3; DCT_STRING=4; DCT_BLOB=7; }
 *
 * Response 为空消息，仅用 HTTP 状态码 + OTS 错误头判断成败。
 */
class DefinedColumnApi
{
    /**
     * 类型字符串 → DefinedColumnType enum 编号。
     *
     * 字符串侧与 SDK `DefinedColumnTypeConst` 常量值一致（'INTEGER' / 'DOUBLE' /
     * 'BOOLEAN' / 'STRING' / 'BLOB'），额外接受 'BINARY' 作为 'BLOB' 的易记别名。
     *
     * @var array<string,int>
     */
    private const TYPE_ENUM_MAP = [
        DefinedColumnTypeConst::DCT_INTEGER => 1,
        DefinedColumnTypeConst::DCT_DOUBLE  => 2,
        DefinedColumnTypeConst::DCT_BOOLEAN => 3,
        DefinedColumnTypeConst::DCT_STRING  => 4,
        DefinedColumnTypeConst::DCT_BLOB    => 7,
        'BINARY'                            => 7,
    ];

    /**
     * @var OTSClient
     */
    private $client;

    public function __construct(OTSClient $client)
    {
        $this->client = $client;
    }

    /**
     * 新增预定义列。与 SDK 其他方法一致，入参为 request 数组。
     *
     *     [
     *         'table_name' => 'xxx',
     *         'columns'    => [
     *             ['name' => 'col1', 'type' => 'STRING'],
     *             ['name' => 'col2', 'type' => 'INTEGER'],
     *         ],
     *     ]
     *
     * type 取 SDK `DefinedColumnTypeConst` 常量值字符串（'INTEGER' / 'DOUBLE' /
     * 'BOOLEAN' / 'STRING' / 'BLOB'），也可用 'BINARY'（= 'BLOB'）。
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function addDefinedColumn(array $request): void
    {
        $table = (string) ($request['table_name'] ?? '');
        $columns = $request['columns'] ?? [];
        if ('' === $table) {
            throw new OTSClientException('AddDefinedColumn: table_name is required.');
        }
        if (empty($columns)) {
            return;
        }
        $body = self::encodeAddDefinedColumnRequest($table, $columns);
        $this->send('AddDefinedColumn', $body);
    }

    /**
     * 删除预定义列。入参为 request 数组。
     *
     *     [
     *         'table_name' => 'xxx',
     *         'columns'    => ['col1', 'col2'],
     *     ]
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    public function deleteDefinedColumn(array $request): void
    {
        $table = (string) ($request['table_name'] ?? '');
        $columnNames = $request['columns'] ?? [];
        if ('' === $table) {
            throw new OTSClientException('DeleteDefinedColumn: table_name is required.');
        }
        $columnNames = array_values(array_filter(array_map('strval', $columnNames), fn($n) => '' !== $n));
        if (empty($columnNames)) {
            return;
        }
        $body = self::encodeDeleteDefinedColumnRequest($table, $columnNames);
        $this->send('DeleteDefinedColumn', $body);
    }

    /**
     * 发送一次 OTS 请求。跳过 ProtoBufferEncoder（SDK 无对应 encode 方法），直接把
     * 手拼好的 body 塞进 RequestContext，复用 HttpHeaderHandler 做签名、HttpClient
     * 发请求、ErrorHandler 解析错误。
     *
     * @throws OTSClientException
     * @throws OTSServerException
     */
    private function send(string $apiName, string $body): void
    {
        $rawHandlers = self::extractHandlers($this->client);
        $handlers = new OTSHandlers($rawHandlers);

        $context = new RequestContext(
            $handlers->clientConfig,
            $handlers->httpClient,
            $apiName,
            [] // 不走 encoder，原始 request 数组不会被使用
        );
        $context->requestBody = $body;

        // 计算并注入签名、Content-MD5 等必备 headers
        $handlers->httpHeaderHandler->handleBefore($context);

        $response = $context->httpClient->request('POST', '/' . $apiName, [
            'body'        => $context->requestBody,
            'headers'     => $context->requestHeaders,
            'timeout'     => $context->clientConfig->socketTimeout,
            'http_errors' => false,
        ]);

        $context->responseBody = (string) $response->getBody();
        $context->responseHttpStatus = $response->getStatusCode();
        $context->responseHeaders = [];
        foreach ($response->getHeaders() as $key => $value) {
            $context->responseHeaders[$key] = $value[0];
        }

        // 非 2xx 响应：ErrorHandler 解析错误并塞到 $context->otsServerException
        $handlers->errorHandler->handleAfter($context);
        if (null !== $context->otsServerException) {
            throw $context->otsServerException;
        }

        // 2xx 响应：校验 headers（md5 / 签名 / 时间漂移）
        if ($context->responseHttpStatus >= 200 && $context->responseHttpStatus < 300) {
            $handlers->httpHeaderHandler->handleAfter($context);
        }
    }

    // ====================================================================
    // protobuf wire format 手工编码。
    //   tag = (field_number << 3) | wire_type
    //   wire_type: 0=varint（int/enum），2=length-delimited（string/embedded）
    // ====================================================================

    /**
     * @internal
     *
     * @param array<int,array{name:string,type:string}> $columns
     *
     * @throws OTSClientException
     */
    public static function encodeAddDefinedColumnRequest(string $table, array $columns): string
    {
        $body = self::encodeStringField(1, $table);

        foreach ($columns as $column) {
            $name = (string) ($column['name'] ?? '');
            $typeName = strtoupper((string) ($column['type'] ?? ''));
            if ('' === $name || '' === $typeName) {
                throw new OTSClientException('AddDefinedColumn: column must have non-empty name and type.');
            }

            $typeEnum = self::TYPE_ENUM_MAP[$typeName] ?? null;
            if (null === $typeEnum) {
                throw new OTSClientException(sprintf(
                    'AddDefinedColumn: unsupported type "%s". Allowed: %s',
                    $typeName,
                    implode(', ', array_keys(self::TYPE_ENUM_MAP))
                ));
            }

            $schemaBody = self::encodeStringField(1, $name) . self::encodeVarintField(2, $typeEnum);
            $body .= self::encodeEmbeddedField(2, $schemaBody);
        }

        return $body;
    }

    /**
     * @internal
     *
     * @param array<int,string> $columnNames
     */
    public static function encodeDeleteDefinedColumnRequest(string $table, array $columnNames): string
    {
        $body = self::encodeStringField(1, $table);
        foreach ($columnNames as $name) {
            $body .= self::encodeStringField(2, (string) $name);
        }

        return $body;
    }

    /**
     * 获取 aliyun SDK OTSClient 内部的 OTSHandlers 实例。
     *
     * aliyun-tablestore-sdk-php 各版本里 $handlers 的可访问性不同：
     *   - <=5.1.4:  public / 无限定符
     *   - 6.0.x:    private，提供 getHandlers() 公开方法
     *   - 某些 dev 分支: private 且无 getHandlers()，但有 asyncDoHandle()
     * 为兼容全部情况，优先调用 getHandlers()，退而使用反射读取 private 属性。
     */
    private static function extractHandlers(OTSClient $client): \Aliyun\OTS\Handlers\OTSHandlers
    {
        if (method_exists($client, 'getHandlers')) {
            return $client->getHandlers();
        }

        $reflection = new \ReflectionClass($client);
        if ($reflection->hasProperty('handlers')) {
            $prop = $reflection->getProperty('handlers');
            $prop->setAccessible(true);

            return $prop->getValue($client);
        }

        throw new OTSClientException('Unable to access OTSClient handlers (unknown SDK version).');
    }

    private static function encodeVarint(int $value): string
    {
        if ($value < 0) {
            throw new OTSClientException('varint encoding: negative value not supported.');
        }

        $out = '';
        while ($value > 0x7F) {
            $out .= chr(($value & 0x7F) | 0x80);
            $value >>= 7;
        }
        $out .= chr($value & 0x7F);

        return $out;
    }

    private static function encodeStringField(int $field, string $value): string
    {
        return chr(($field << 3) | 2) . self::encodeVarint(strlen($value)) . $value;
    }

    private static function encodeEmbeddedField(int $field, string $body): string
    {
        return chr(($field << 3) | 2) . self::encodeVarint(strlen($body)) . $body;
    }

    private static function encodeVarintField(int $field, int $value): string
    {
        return chr(($field << 3) | 0) . self::encodeVarint($value);
    }
}
