<?php

namespace ScayTrase\Api\Rpc\Decorators;

use ScayTrase\Api\Rpc\RpcRequestInterface;

interface CacheKeyStrategyInterface
{
    /**
     * Create cache key for given RPC
     *
     * @param RpcRequestInterface $request
     *
     * @return string
     */
    public function getKey(RpcRequestInterface $request);
}
