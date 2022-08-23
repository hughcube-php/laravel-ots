<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/8/23
 * Time: 14:53
 */

namespace HughCube\Laravel\OTS\OTS\Handlers;

use Aliyun\OTS\OTSClientException;
use Exception;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @property GuzzleHttpClient $httpClient
 * @property null|Exception $otsServerException
 */
class RequestContext extends \Aliyun\OTS\Handlers\RequestContext
{
    /**
     * @var PromiseInterface|null
     */
    protected $hPromise = null;

    /**
     * @var ResponseInterface|null
     */
    protected $hHttpResponse = null;

    /**
     * @var OTSHandlers
     */
    protected $otsHandlers = null;

    public $responseReasonPhrase;

    public function withHPromise($promise): RequestContext
    {
        $this->hPromise = $promise;

        return $this;
    }

    public function withHHandlers($handlers): RequestContext
    {
        $this->otsHandlers = $handlers;
        return $this;
    }

    protected function getHHttpResponse(): ResponseInterface
    {
        if (null !== $this->hPromise && !$this->hHttpResponse instanceof ResponseInterface) {
            $this->hHttpResponse = $this->hPromise->wait();
        }
        return $this->hHttpResponse;
    }

    /**
     * @throws OTSClientException
     * @throws Exception
     */
    public function HWait()
    {
        $httpResponse = $this->getHHttpResponse();

        $this->responseHeaders = [];
        $headers = $httpResponse->getHeaders();
        foreach ($headers as $key => $value) {
            $this->responseHeaders[$key] = $value[0];
        }

        $this->responseBody = (string)$httpResponse->getBody();
        $this->responseHttpStatus = $httpResponse->getStatusCode();
        $this->responseReasonPhrase = $httpResponse->getReasonPhrase();

        $this->otsHandlers->httpHandler->handleAfter($this);
        $this->otsHandlers->httpHeaderHandler->handleAfter($this);
        $this->otsHandlers->errorHandler->handleAfter($this);
        $this->otsHandlers->protoBufferEncoder->handleAfter($this);
        $this->otsHandlers->protoBufferDecoder->handleAfter($this);
        $this->otsHandlers->retryHandler->handleAfter($this);

        if (null !== $this->otsServerException) {
            throw $this->otsServerException;
        }

        return $this->response;
    }

    public function __destruct()
    {
        $this->getHHttpResponse();
    }
}
