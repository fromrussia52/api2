<?php

namespace ScayTrase\Api\Rpc\Decorators;

use Psr\Cache\CacheItemPoolInterface;
use ScayTrase\Api\Rpc\RpcClientInterface;

final class CacheableRpcClient implements RpcClientInterface
{
    const DEFAULT_KEY_PREFIX = 'rpc_client_cache';

    /** @var CacheItemPoolInterface */
    private $cache;
    /** @var RpcClientInterface */
    private $decoratedClient;
    /** @var CacheKeyStrategyInterface */
    private $keyStrategy;
    /** @var int|null */
    private $ttl;

    /**
     * CacheableRpcClient constructor.
     *
     * @param RpcClientInterface               $decoratedClient
     * @param CacheItemPoolInterface           $cache
     * @param int|null                         $ttl
     * @param CacheKeyStrategyInterface|string $strategy
     */
    public function __construct(
        RpcClientInterface $decoratedClient,
        CacheItemPoolInterface $cache,
        $ttl = null,
        $strategy = self::DEFAULT_KEY_PREFIX
    ) {
        $this->decoratedClient = $decoratedClient;
        $this->cache           = $cache;
        $this->ttl             = $ttl;

        if (!$strategy instanceof CacheKeyStrategyInterface) {
            $this->keyStrategy = new Sha1KeyStrategy((string)$strategy);
        }
    }

    /** {@inheritdoc} */
    public function invoke($calls)
    {
        $isArray = true;
        if (!is_array($calls)) {
            $isArray = false;
            $calls = [$calls];
        }

        $items           = [];
        $proxiedRequests = [];
        foreach ($calls as $call) {
            $key                    = $this->keyStrategy->getKey($call);
            $item                   = $this->cache->getItem($key);
            $items[$key]['request'] = $call;
            $items[$key]['item']    = $item;
            if (!$item->isHit()) {
                $proxiedRequests[] = $call;
            }
        }

        // Prevent batch calls when not necessary
        if (count($proxiedRequests) === 1 && !$isArray) {
            $proxiedRequests = array_shift($proxiedRequests);
        }

        return new CacheableResponseCollection(
            $this->cache,
            $this->keyStrategy,
            $items,
            $this->decoratedClient->invoke($proxiedRequests),
            $this->ttl
        );
    }
}
