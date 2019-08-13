<?php

namespace ScayTrase\Api\Rpc\Test;

use ScayTrase\Api\Rpc\Exception\RpcExceptionInterface;
use ScayTrase\Api\Rpc\RpcRequestInterface;
use ScayTrase\Api\Rpc\RpcResponseInterface;

class MockClientException extends \RuntimeException implements RpcExceptionInterface
{
    /** @var RpcRequestInterface */
    private $request;
    /** @var RpcResponseInterface */
    private $response;

    public static function emptyQueue(RpcRequestInterface $request)
    {
        $ex = new static(
            sprintf('Mock queue is empty while calling "%s"', $request->getMethod())
        );

        $ex->request = $request;

        return $ex;
    }

    public static function filterFailed(RpcRequestInterface $request, RpcResponseInterface $response)
    {
        $ex           = new static('Request structure declined by filter');
        $ex->request  = $request;
        $ex->response = $response;

        return $ex;
    }

    /**
     * @return RpcRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return RpcResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}
