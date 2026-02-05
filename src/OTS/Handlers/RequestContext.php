<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/8/23
 * Time: 14:53.
 */

namespace HughCube\Laravel\OTS\OTS\Handlers;

use Aliyun\OTS\OTSClientException;
use Exception;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * @property GuzzleHttpClient $httpClient
 * @property null|Exception   $otsServerException
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
     * @var OTSHandlers|null
     */
    protected $otsHandlers = null;

    /**
     * @var bool Whether HWait() has been called
     */
    protected $hWaitCalled = false;

    /**
     * @var mixed Cached response from HWait()
     */
    protected $hCachedResponse = null;

    /**
     * @var Throwable|null Cached exception from HWait()
     */
    protected $hCachedException = null;

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

    /**
     * Get the HTTP response, waiting for Promise if needed.
     */
    protected function getHHttpResponse(): ?ResponseInterface
    {
        if (null !== $this->hPromise && !$this->hHttpResponse instanceof ResponseInterface) {
            $this->hHttpResponse = $this->hPromise->wait();
        }

        return $this->hHttpResponse;
    }

    /**
     * Check if the async request has completed.
     */
    public function isCompleted(): bool
    {
        return $this->hWaitCalled;
    }

    /**
     * Check if the async request is still pending.
     */
    public function isPending(): bool
    {
        return !$this->hWaitCalled && $this->hPromise !== null;
    }

    /**
     * Wait for the async request to complete and return the response.
     *
     * @throws OTSClientException
     * @throws Exception
     *
     * @return mixed
     */
    public function HWait()
    {
        // If already called, return cached result or throw cached exception
        if ($this->hWaitCalled) {
            if ($this->hCachedException !== null) {
                throw $this->hCachedException;
            }

            return $this->hCachedResponse;
        }

        $this->hWaitCalled = true;

        try {
            if ($this->otsHandlers === null) {
                throw new OTSClientException('OTSHandlers not set. Use asyncDoHandle() to create RequestContext.');
            }

            $httpResponse = $this->getHHttpResponse();

            if ($httpResponse === null) {
                throw new OTSClientException('No HTTP response available');
            }

            $this->responseHeaders = [];
            $headers = $httpResponse->getHeaders();
            foreach ($headers as $key => $value) {
                $this->responseHeaders[$key] = $value[0];
            }

            $this->responseBody = (string) $httpResponse->getBody();
            $this->responseHttpStatus = $httpResponse->getStatusCode();
            $this->responseReasonPhrase = $httpResponse->getReasonPhrase();

            $this->otsHandlers->httpHandler->handleAfter($this);
            $this->otsHandlers->httpHeaderHandler->handleAfter($this);
            $this->otsHandlers->errorHandler->handleAfter($this);
            $this->otsHandlers->protoBufferEncoder->handleAfter($this);
            $this->otsHandlers->protoBufferDecoder->handleAfter($this);
            $this->otsHandlers->retryHandler->handleAfter($this);

            if (null !== $this->otsServerException) {
                $this->hCachedException = $this->otsServerException;
                throw $this->otsServerException;
            }

            $this->hCachedResponse = $this->response;

            return $this->response;
        } catch (Throwable $e) {
            $this->hCachedException = $e;
            throw $e;
        }
    }

    /**
     * Get the response without throwing exceptions.
     * Returns null if request failed.
     *
     * @return mixed|null
     */
    public function getResponse()
    {
        try {
            return $this->HWait();
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Get the exception if the request failed.
     */
    public function getException(): ?Throwable
    {
        if (!$this->hWaitCalled) {
            try {
                $this->HWait();
            } catch (Throwable $e) {
                // Exception is now cached
            }
        }

        return $this->hCachedException;
    }

    /**
     * Check if the request failed.
     */
    public function failed(): bool
    {
        return $this->getException() !== null;
    }

    /**
     * Check if the request succeeded.
     */
    public function successful(): bool
    {
        if (!$this->hWaitCalled) {
            try {
                $this->HWait();
            } catch (Throwable $e) {
                return false;
            }
        }

        return $this->hCachedException === null;
    }

    /**
     * Destructor - safely wait for pending request.
     */
    public function __destruct()
    {
        // Safely wait for HTTP response to complete
        // Don't throw exceptions in destructor
        try {
            $this->getHHttpResponse();
        } catch (Throwable $e) {
            // Silently ignore - user should call HWait() to handle exceptions
        }
    }
}
