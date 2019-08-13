<?php

namespace ScayTrase\Api\Rpc\Decorators;

use ScayTrase\Api\Rpc\RpcRequestInterface;

/** @internal */
final class Sha1KeyStrategy implements CacheKeyStrategyInterface
{
    /** @var string */
    private $keyPrefix;

    /**
     * Sha1KeyStrategy constructor.
     *
     * @param string $keyPrefix
     */
    public function __construct($keyPrefix)
    {
        $this->keyPrefix = $keyPrefix;
    }

    public function getKey(RpcRequestInterface $request)
    {
        $data = [
            'method' => (string)$request->getMethod(),
            'params' => json_decode(json_encode($request->getParameters()), true),
        ];

        $stringData = json_encode($data);

        return $this->keyPrefix.sha1($stringData);
    }
}
