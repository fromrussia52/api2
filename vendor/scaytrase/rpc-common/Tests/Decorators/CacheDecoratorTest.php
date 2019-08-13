<?php

namespace ScayTrase\Api\Rpc\Tests\Decorators;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use ScayTrase\Api\Rpc\Decorators\CacheableRpcClient;
use ScayTrase\Api\Rpc\Tests\RpcRequestTrait;

final class CacheDecoratorTest extends TestCase
{
    use RpcRequestTrait;

    public function testResponseCachingArray()
    {
        $rq1 = $this->getRequestMock('/test1', ['param1' => 'test']);
        $rq2 = $this->getRequestMock('/test1', ['param1' => 'test']);
        $rs1 = $this->getResponseMock(true, ['payload' => uniqid('test', true)]);

        $cache = $this->createCacheMock();

        $client = new CacheableRpcClient($this->getClientMock([$rq1], [$rs1]), $cache, 5);
        $response = $client->invoke([$rq1])->getResponse($rq1);
        self::assertEquals($rs1, $response);

        $client = new CacheableRpcClient($this->getClientMock(), $cache, 5);
        $response = $client->invoke([$rq2])->getResponse($rq2);
        self::assertEquals($rs1, $response);

        self::assertEquals($rs1, $client->invoke($rq1)->getResponse($rq1));
        self::assertEquals($rs1, $client->invoke($rq2)->getResponse($rq2));
    }

    public function testResponseCachingSingle()
    {
        $rq1 = $this->getRequestMock('/test1', ['param1' => 'test']);
        $rq2 = $this->getRequestMock('/test1', ['param1' => 'test']);
        $rs1 = $this->getResponseMock(true, ['payload' => uniqid('test', true)]);

        $cache = $this->createCacheMock();

        $client = new CacheableRpcClient($this->getClientMock([$rq1], [$rs1]), $cache, 5);
        self::assertEquals($rs1, $client->invoke($rq1)->getResponse($rq1));
        self::assertEquals($rs1, $client->invoke($rq1)->getResponse($rq1));

        $client = new CacheableRpcClient($this->getClientMock(), $cache, 5);
        self::assertEquals($rs1, $client->invoke($rq2)->getResponse($rq2));
        self::assertEquals($rs1, $client->invoke($rq2)->getResponse($rq2));
    }

    public function testResponseCachingIterator()
    {
        $rq1 = $this->getRequestMock('/test1', ['param1' => 'test']);
        $rq2 = $this->getRequestMock('/test1', ['param1' => 'test']);
        $rs1 = $this->getResponseMock(true, ['payload' => uniqid('test', true)]);

        $cache = $this->createCacheMock();

        $client = new CacheableRpcClient($this->getClientMock([$rq1], [$rs1]), $cache, 5);
        foreach ($client->invoke([$rq1]) as $response) {
            self::assertEquals($rs1, $response);
        }

        $client = new CacheableRpcClient($this->getClientMock(), $cache, 5);
        foreach ($client->invoke([$rq2]) as $response) {
            self::assertEquals($rs1, $response);
        }
    }

    /**
     * @return CacheItemPoolInterface
     */
    private function createCacheMock()
    {
        $items = new \ArrayObject();
        $cache = $this->prophesize(CacheItemPoolInterface::class);
        $that = $this;
        $cache->getItem(Argument::type('string'))->will(
            function ($args) use ($items, $that) {
                $key = $args[0];
                if (!array_key_exists($key, $items)) {
                    $item = $that->prophesize(CacheItemInterface::class);

                    $item->getKey()->willReturn($key);
                    $item->isHit()->willReturn(false);
                    $item->set(Argument::any())->will(
                        function ($args) use ($item) {
                            $item->get()->willReturn($args[0]);
                        }
                    );
                    $item->expiresAfter(Argument::type('int'))->willReturn($item);
                    $item->expiresAfter(Argument::exact(null))->willReturn($item);
                    $item->expiresAfter(Argument::type(\DateInterval::class))->willReturn($item);
                    $item->expiresAt(Argument::type(\DateTimeInterface::class))->willReturn($item);
                    $items[$key] = $item;
                }

                return $items[$key]->reveal();
            }
        );
        $cache->save(Argument::type(CacheItemInterface::class))->will(
            function ($args) use ($items) {
                $item = $args[0];
                $items[$item->getKey()]->isHit()->willReturn(true);
            }
        );

        return $cache->reveal();
    }
}
