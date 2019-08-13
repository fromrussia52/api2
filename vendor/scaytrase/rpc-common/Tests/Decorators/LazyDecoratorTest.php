<?php

namespace ScayTrase\Api\Rpc\Tests\Decorators;

use PHPUnit\Framework\TestCase;
use ScayTrase\Api\Rpc\Decorators\LazyResponseCollection;
use ScayTrase\Api\Rpc\Decorators\LazyRpcClient;
use ScayTrase\Api\Rpc\RpcRequestInterface;
use ScayTrase\Api\Rpc\RpcResponseInterface;
use ScayTrase\Api\Rpc\Tests\RpcRequestTrait;

final class LazyDecoratorTest extends TestCase
{
    use RpcRequestTrait;

    public function testLazyRequestsArray()
    {
        /** @var LazyResponseCollection $c1 */
        list($requests, $responses, $c1) = $this->createCollection();

        foreach ($requests as $id => $request) {
            $response = $c1->getResponse($request);

            self::assertNotNull($response);
            self::assertTrue($c1->isFrozen());
            self::assertEquals($response, $responses[$id]);
            self::assertTrue($response->isSuccessful());
            self::assertInstanceOf(\stdClass::class, $response->getBody());
            self::assertEquals($request->getParameters(), $response->getBody());
        }
    }

    public function testLazyRequestsIterator()
    {
        /** @var LazyResponseCollection $c1 */
        list($requests, $responses, $c1) = $this->createCollection();

        foreach ($c1 as $response) {
            self::assertContains($response, $responses);
        }

        foreach ($c1 as $response) {
            self::assertContains($response, $responses);
        }
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot add request to frozen lazy collection
     */
    public function testFrozenException()
    {
        /** @var LazyResponseCollection $c1 */
        list($requests, $responses, $c1) = $this->createCollection();

        $r1 = array_shift($requests);

        $c1->getResponse($r1);
        self::assertTrue($c1->isFrozen());
        $c1->append($r1);
    }

    public function testInvokingFrozenClientCreatesNewCollection()
    {
        $rq1 = $this->getRequestMock('/test1', ['param1' => 'test']);
        $rq2 = $this->getRequestMock('/test2', ['param2' => 'test']);

        $rs1 = $this->getResponseMock(true, (object)['param1' => 'test']);
        $rs2 = $this->getResponseMock(true, (object)['param2' => 'test']);

        $client = new LazyRpcClient($this->getClientMock([$rq1, $rq2], [$rs1, $rs2]));
        /** @var LazyResponseCollection $c1 */
        $c1 = $client->invoke($rq1);
        self::assertEquals($rs1, $c1->getResponse($rq1));
        self::assertTrue($c1->isFrozen());
        /** @var LazyResponseCollection $c2 */
        $c2 = $client->invoke($rq2);
        self::assertEquals($rs2, $c2->getResponse($rq2));
        self::assertTrue($c2->isFrozen());
        self::assertNotEquals($c1, $c2);
    }

    /**
     * @return array
     */
    private function createCollection()
    {
        $rq1 = $this->getRequestMock('/test1', ['param1' => 'test']);
        $rq2 = $this->getRequestMock('/test2', ['param2' => 'test']);
        $rq3 = $this->getRequestMock('/test3', ['param3' => 'test']);

        $rs1 = $this->getResponseMock(true, (object)['param1' => 'test']);
        $rs2 = $this->getResponseMock(true, (object)['param2' => 'test']);
        $rs3 = $this->getResponseMock(true, (object)['param3' => 'test']);

        /** @var RpcRequestInterface[] $requests */
        $requests = [$rq1, $rq2, $rq3];
        /** @var RpcResponseInterface[] $responses */
        $responses = [$rs1, $rs2, $rs3];

        $client = $this->getClientMock($requests, $responses);

        $lazyClient = new LazyRpcClient($client);

        $c1 = $lazyClient->invoke($rq1);
        $c2 = $lazyClient->invoke($rq2);
        $c3 = $lazyClient->invoke($rq3);

        self::assertEquals($c1, $c2);
        self::assertEquals($c1, $c3);

        self::assertFalse($c1->isFrozen());

        return array($requests, $responses, $c1);
    }
}
