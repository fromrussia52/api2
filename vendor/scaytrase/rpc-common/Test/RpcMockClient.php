<?php

namespace ScayTrase\Api\Rpc\Test;

use ScayTrase\Api\Rpc\RpcClientInterface;
use ScayTrase\Api\Rpc\RpcRequestInterface;
use ScayTrase\Api\Rpc\RpcResponseInterface;

final class RpcMockClient implements RpcClientInterface, \Countable
{
    private $queue = [];

    /** {@inheritdoc} */
    public function invoke($calls)
    {
        if (!is_array($calls)) {
            $calls = [$calls];
        }

        $tuples = [];
        foreach ((array)$calls as $call) {
            $tuples[] = [
                'request'  => $call,
                'response' => $this->call($call),
            ];
        }

        return new TupleCollection($tuples);
    }

    public function push(RpcResponseInterface $response, callable $filter = null)
    {
        $this->queue[] = [$response, $filter];
    }

    /**
     * @return array
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /** {@inheritdoc} */
    public function count()
    {
        return count($this->queue);
    }

    private function call(RpcRequestInterface $request)
    {
        $tip = array_shift($this->queue);

        if (null === $tip) {
            throw MockClientException::emptyQueue($request);
        }

        list($response, $filter) = $tip;

        if (is_callable($filter) && !$filter($request)) {
            throw MockClientException::filterFailed($request, $response);
        }

        return $response;
    }
}
