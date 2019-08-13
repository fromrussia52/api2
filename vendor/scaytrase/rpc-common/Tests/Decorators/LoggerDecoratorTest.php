<?php

namespace ScayTrase\Api\Rpc\Tests\Decorators;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_Matcher_Invocation as InvocationExpectation;
use Psr\Log\LoggerInterface;
use ScayTrase\Api\Rpc\Decorators\LoggableRpcClient;
use ScayTrase\Api\Rpc\Tests\RpcRequestTrait;

final class LoggerDecoratorTest extends TestCase
{
    use RpcRequestTrait;

    public function testLoggingSuccessfulResponseArray()
    {
        $rq1    = $this->getRequestMock('/test1', ['param1' => 'test']);
        $rs1    = $this->getResponseMock(true, ['param1' => 'test']);
        $client =
            new LoggableRpcClient($this->getClientMock([$rq1], [$rs1]), $this->createLoggerMock(self::atLeastOnce()));

        $collection = $client->invoke([$rq1]);
        self::assertEquals($rs1, $collection->getResponse($rq1));
    }

    public function testDebugFlagEnabledDebug()
    {
        $rq1    = $this->getRequestMock('/test1', ['param1' => 'test']);
        $rs1    = $this->getResponseMock(true, ['param1' => 'test']);
        $client =
            new LoggableRpcClient(
                $this->getClientMock([$rq1], [$rs1]),
                $this->createLoggerMock(
                    self::atLeastOnce(),
                    self::atLeastOnce()
                ),
                true
            );

        $collection = $client->invoke([$rq1]);
        self::assertEquals($rs1, $collection->getResponse($rq1));
    }

    public function testLoggingFailedResponseArray()
    {
        $rq1    = $this->getRequestMock('/test2', ['param1' => 'test']);
        $rs1    = $this->getResponseMock(false, null, $this->getErrorMock(0, 'invalid'));
        $client =
            new LoggableRpcClient($this->getClientMock([$rq1], [$rs1]), $this->createLoggerMock(self::atLeastOnce()));

        $collection = $client->invoke([$rq1]);
        self::assertEquals($rs1, $collection->getResponse($rq1));
    }

    public function testLoggingSuccessfulResponseIterator()
    {
        $rq1    = $this->getRequestMock('/test1', ['param1' => 'test']);
        $rs1    = $this->getResponseMock(true, ['param1' => 'test']);
        $client =
            new LoggableRpcClient($this->getClientMock([$rq1], [$rs1]), $this->createLoggerMock(self::atLeastOnce()));

        $collection = $client->invoke($rq1);
        foreach ($collection as $response) {
            self::assertEquals($rs1, $response);
        }

        // add check on log count
        self::assertEquals($rs1, $collection->getResponse($rq1));

        foreach ($collection as $response) {
            self::assertEquals($rs1, $response);
        }
    }

    public function testLoggingFailedResponseIterator()
    {
        $rq1    = $this->getRequestMock('/test2', ['param1' => 'test']);
        $rs1    = $this->getResponseMock(false, null, $this->getErrorMock(0, 'invalid'));
        $client =
            new LoggableRpcClient($this->getClientMock([$rq1], [$rs1]), $this->createLoggerMock(self::atLeastOnce()));

        $collection = $client->invoke($rq1);
        foreach ($collection as $response) {
            self::assertEquals($rs1, $response);
        }
    }

    /**
     * @param InvocationExpectation $infoExpectation
     * @param InvocationExpectation $debugExpectation
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createLoggerMock(
        InvocationExpectation $infoExpectation = null,
        InvocationExpectation $debugExpectation = null
    ) {
        $infoExpectation  = $infoExpectation ?: self::never();
        $debugExpectation = $debugExpectation ?: self::never();
        $logger           = self::getMockBuilder(LoggerInterface::class)->getMock();
        $logger->expects($infoExpectation)->method('info');
        $logger->expects($debugExpectation)->method('debug');

        return $logger;
    }
}
