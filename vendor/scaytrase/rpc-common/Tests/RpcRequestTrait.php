<?php

namespace ScayTrase\Api\Rpc\Tests;

use Prophecy\Argument;
use ScayTrase\Api\Rpc\ResponseCollectionInterface;
use ScayTrase\Api\Rpc\RpcClientInterface;
use ScayTrase\Api\Rpc\RpcErrorInterface;
use ScayTrase\Api\Rpc\RpcRequestInterface;
use ScayTrase\Api\Rpc\RpcResponseInterface;

trait RpcRequestTrait
{
    /**
     * @param string $method
     * @param array $params
     *
     * @return RpcRequestInterface
     */
    protected function getRequestMock($method, array $params = [])
    {
        $request = $this->prophesize(RpcRequestInterface::class);
        $request->getMethod()->willReturn($method);
        $request->getParameters()->willReturn((object)$params);

        return $request->reveal();
    }

    /**
     * @param bool $success
     * @param \stdClass|array|null|mixed $body
     * @param RpcErrorInterface $error
     *
     * @return RpcResponseInterface
     */
    protected function getResponseMock($success = true, $body = null, RpcErrorInterface $error = null)
    {
        $mock = $this->prophesize(RpcResponseInterface::class);
        $mock->isSuccessful()->willReturn($success);
        $mock->getError()->willReturn($success ? null : $error);
        $mock->getBody()->willReturn($success ? $body : null);

        return $mock->reveal();
    }

    /**
     * @param mixed $code
     * @param string $message
     *
     * @return RpcErrorInterface
     */
    protected function getErrorMock($code, $message)
    {
        $mock = $this->prophesize(RpcErrorInterface::class);
        $mock->getCode()->willReturn($code);
        $mock->getMessage()->willReturn($message);

        return $mock->reveal();
    }

    /**
     * @param RpcRequestInterface[] $requests
     * @param RpcResponseInterface[] $responses
     *
     * @return RpcClientInterface
     */
    protected function getClientMock(array $requests = [], array $responses = [])
    {
        self::assertEquals(count($requests), count($responses));

        $client = $this->prophesize(RpcClientInterface::class);
        $that = $this;
        $client->invoke(Argument::type('array'))->will(
            function ($args) use ($that, $requests, $responses) {
                $collection = $that->prophesize(ResponseCollectionInterface::class);
                $collection->willImplement(\IteratorAggregate::class);
                $cR = [];
                foreach ($requests as $key => $request) {
                    if (in_array($request, $args[0], true)) {
                        $collection->getResponse(Argument::exact($request))->willReturn($responses[$key]);
                        $cR[] = $responses[$key];
                    }
                }

                $collection->getIterator()->willReturn(new \ArrayIterator($cR));

                return $collection->reveal();
            }
        );

        $client->invoke(Argument::type(RpcRequestInterface::class))->will(
            function ($args) use ($that, $requests, $responses) {
                $collection = $that->prophesize(ResponseCollectionInterface::class);
                $collection->willImplement(\IteratorAggregate::class);
                $cR = [];
                foreach ($requests as $key => $request) {
                    if ($request === $args[0]) {
                        $collection->getResponse(Argument::exact($request))->willReturn($responses[$key]);
                        $cR[] = $responses[$key];
                    }
                }

                $collection->getIterator()->willReturn(new \ArrayIterator($cR));

                return $collection->reveal();
            }
        );

        return $client->reveal();
    }
}
