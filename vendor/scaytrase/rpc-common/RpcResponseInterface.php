<?php

namespace ScayTrase\Api\Rpc;

interface RpcResponseInterface
{
    /** @return bool */
    public function isSuccessful();

    /** @return RpcErrorInterface|null */
    public function getError();

    /** @return \stdClass|array|mixed|null */
    public function getBody();
}
