<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/8/23
 * Time: 15:02
 */

namespace HughCube\Laravel\OTS\OTS\Handlers;

use Aliyun\OTS\Handlers\ErrorHandler;
use Aliyun\OTS\Handlers\HttpHandler;
use Aliyun\OTS\Handlers\HttpHeaderHandler;
use Aliyun\OTS\Handlers\OTSHandlers as AliyunOTSHandlers;
use Aliyun\OTS\Handlers\ProtoBufferDecoder;
use Aliyun\OTS\Handlers\ProtoBufferEncoder;
use Aliyun\OTS\Handlers\RetryHandler;
use Aliyun\OTS\OTSClientConfig;
use GuzzleHttp\Client as GuzzleHttpClient;
use ReflectionClass;

/**
 * @property-read OTSClientConfig $clientConfig
 * @property-read GuzzleHttpClient $httpClient
 * @property-read RetryHandler $retryHandler
 * @property-read ProtoBufferDecoder $protoBufferDecoder
 * @property-read ProtoBufferEncoder $protoBufferEncoder
 * @property-read ErrorHandler $errorHandler
 * @property-read HttpHeaderHandler $httpHeaderHandler
 * @property-read HttpHandler $httpHandler
 */
class OTSHandlers
{
    /**
     * @var AliyunOTSHandlers
     */
    protected $OTSHandlers;

    public function __construct($handler)
    {
        $this->OTSHandlers = $handler;
    }

    public function doHandle($apiName, array $request)
    {
        return $this->OTSHandlers->doHandle($apiName, $request);
    }

    public function asyncDoHandle($apiName, array $request): RequestContext
    {
        $context = new RequestContext($this->clientConfig, $this->httpClient, $apiName, $request);

        $this->retryHandler->handleBefore($context);
        $this->protoBufferDecoder->handleBefore($context);
        $this->protoBufferEncoder->handleBefore($context);
        $this->errorHandler->handleBefore($context);
        $this->httpHeaderHandler->handleBefore($context);

        $promise = $context->httpClient->requestAsync('POST', ('/'.$context->apiName), [
            'body' => $context->requestBody,
            'headers' => $context->requestHeaders,
            'timeout' => $context->clientConfig->socketTimeout,
            'http_errors' => false, // don't throw exception when HTTP protocol errors are encountered
        ]);

        return $context->withHPromise($promise)->withHHandlers($this);
    }

    public function __get($name)
    {
        $reflection = new ReflectionClass($this->OTSHandlers);
        if ($reflection->hasProperty($name)) {
            $property = $reflection->getProperty($name);
            $property->setAccessible(true);
            return $property->getValue($this->OTSHandlers);
        }

        return $this->OTSHandlers->{$name};
    }
}
