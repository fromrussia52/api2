<?php

namespace ScayTrase\Api\Rpc;

interface RpcErrorInterface
{
    /** @return int */
    public function getCode();

    /** @return string */
    public function getMessage();
}
