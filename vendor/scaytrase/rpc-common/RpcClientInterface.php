<?php

namespace ScayTrase\Api\Rpc;

use ScayTrase\Api\Rpc\Exception\RpcExceptionInterface;

interface RpcClientInterface
{
    /**
     * @param RpcRequestInterface|RpcRequestInterface[] $calls
     * @return ResponseCollectionInterface
     *
     * @throws RpcExceptionInterface
     */
    public function invoke($calls);
}
