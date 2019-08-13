<?php

namespace ScayTrase\Api\Rpc\Decorators;

use ScayTrase\Api\Rpc\ResponseCollectionInterface;
use ScayTrase\Api\Rpc\RpcClientInterface;
use ScayTrase\Api\Rpc\RpcRequestInterface;

final class LazyResponseCollection implements \IteratorAggregate, ResponseCollectionInterface
{
    /** @var bool */
    private $initialized = false;
    /** @var RpcRequestInterface[] */
    private $requests = [];
    /** @var RpcClientInterface */
    private $client;
    /** @var ResponseCollectionInterface */
    private $collection;

    /**
     * LazyResponseCollection constructor.
     *
     * @param RpcClientInterface $client
     */
    public function __construct(RpcClientInterface $client)
    {
        $this->client = $client;
    }

    /** {@inheritdoc} */
    public function getResponse(RpcRequestInterface $request)
    {
        if (!$this->isFrozen()) {
            $this->init();
        }

        return $this->collection->getResponse($request);
    }

    public function append(RpcRequestInterface $request)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Cannot add request to frozen lazy collection');
        }

        $this->requests[] = $request;
    }

    public function isFrozen()
    {
        return $this->initialized;
    }

    /** {@inheritdoc} */
    public function getIterator()
    {
        if (!$this->isFrozen()) {
            $this->init();
        }

        return $this->collection;
    }

    private function init()
    {
        $this->collection  = $this->client->invoke($this->requests);
        $this->requests    = [];
        $this->initialized = true;
    }
}
