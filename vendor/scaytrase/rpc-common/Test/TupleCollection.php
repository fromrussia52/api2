<?php

namespace ScayTrase\Api\Rpc\Test;

use ScayTrase\Api\Rpc\ResponseCollectionInterface;
use ScayTrase\Api\Rpc\RpcRequestInterface;

/**
 * @internal
 */
final class TupleCollection implements \IteratorAggregate, ResponseCollectionInterface
{
    private $tuples = [];

    /**
     * TypleCollection constructor.
     *
     * @param array $tuples
     */
    public function __construct(array $tuples)
    {
        $this->tuples = $tuples;
    }

    /** {@inheritdoc} */
    public function getResponse(RpcRequestInterface $request)
    {
        foreach ($this->tuples as $tuple) {
            if ($tuple['request'] === $request) {
                return $tuple['response'];
            }
        }

        throw new \OutOfBoundsException('Request is not valid');
    }

    /** {@inheritdoc} */
    public function getIterator()
    {
        return new \ArrayIterator(array_map([$this, 'getResponseFromTuple'], $this->tuples));
    }

    private function getResponseFromTuple(array $tuple)
    {
        return $tuple['response'];
    }
}
