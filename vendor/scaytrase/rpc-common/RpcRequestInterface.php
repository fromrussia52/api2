<?php

namespace ScayTrase\Api\Rpc;

interface RpcRequestInterface
{
    /** @return string */
    public function getMethod();

    /** @return \stdClass|array|null */
    public function getParameters();
}
