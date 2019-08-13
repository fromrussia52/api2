<?php

namespace ScayTrase\Api\Rpc\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @deprecated use RpcRequestTrait instead to avoid inheritance lock
 */
abstract class AbstractRpcTest extends TestCase
{
    use RpcRequestTrait;
}
