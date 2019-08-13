<?php

namespace ScayTrase\Api\Rpc\Decorators;

use Psr\Log\LoggerInterface;
use ScayTrase\Api\Rpc\ResponseCollectionInterface;
use ScayTrase\Api\Rpc\RpcRequestInterface;
use ScayTrase\Api\Rpc\RpcResponseInterface;

final class LoggableResponseCollection implements \IteratorAggregate, ResponseCollectionInterface
{
    /** @var  LoggerInterface */
    private $logger;
    /** @var  ResponseCollectionInterface */
    private $decoratedCollection;
    /** @var string[] */
    private $loggedResponses = [];
    /**
     * @var bool
     */
    private $debug;

    /**
     * LoggableResponseCollection constructor.
     *
     * @param ResponseCollectionInterface $decoratedCollection
     * @param LoggerInterface             $logger
     * @param bool                        $debug
     */
    public function __construct(
        ResponseCollectionInterface $decoratedCollection,
        LoggerInterface $logger,
        $debug = false
    ) {
        $this->decoratedCollection = $decoratedCollection;
        $this->logger              = $logger;
        $this->debug               = $debug;
    }

    /** {@inheritdoc} */
    public function getResponse(RpcRequestInterface $request)
    {
        $response = $this->decoratedCollection->getResponse($request);
        $this->logResponseWithRequest($request, $response);

        return $response;
    }

    /** {@inheritdoc} */
    public function getIterator()
    {
        foreach ($this->decoratedCollection as $response) {
            $this->logResponse($response);
            yield $response;
        }
    }

    /**
     * @param RpcRequestInterface  $request
     * @param RpcResponseInterface $response
     */
    private function logResponseWithRequest(RpcRequestInterface $request, RpcResponseInterface $response)
    {
        $hash = spl_object_hash($response);
        if (in_array($hash, $this->loggedResponses, true)) {
            return;
        }

        if ($response->isSuccessful()) {
            $this->logger->info(
                sprintf('Method "%s" call was successful', $request->getMethod()),
                ['request_hash' => spl_object_hash($request)]
            );
            if ($this->debug) {
                $this->logger->debug(
                    sprintf("Response:\n%s", json_encode($response->getBody(), JSON_PRETTY_PRINT)),
                    ['request_hash' => spl_object_hash($request)]
                );
            }
        } else {
            $this->logger->info(
                sprintf('Method "%s" call was failed', $request->getMethod()),
                ['request_hash' => spl_object_hash($request)]
            );
            $this->logger->error(
                sprintf('ERROR %s: %s', $response->getError()->getCode(), $response->getError()->getMessage()),
                ['request_hash' => spl_object_hash($request)]
            );
        }

        $this->loggedResponses[] = $hash;
    }

    private function logResponse(RpcResponseInterface $response)
    {
        $hash = spl_object_hash($response);
        if (in_array($hash, $this->loggedResponses, true)) {
            return;
        }

        if ($response->isSuccessful()) {
            $this->logger->info('Successful RPC call');
            if ($this->debug) {
                $this->logger->debug(
                    sprintf("Response:\n%s", json_encode($response->getBody(), JSON_PRETTY_PRINT))
                );
            }
        } else {
            $this->logger->error(
                sprintf('RPC Error %s: %s', $response->getError()->getCode(), $response->getError()->getMessage())
            );
        }

        $this->loggedResponses[] = $hash;
    }
}
