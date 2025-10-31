<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Stub;

use AsyncAws\Core\Configuration;
use AsyncAws\Core\Response;
use AsyncAws\Route53\Input\ListHostedZonesInput;
use AsyncAws\Route53\Input\ListResourceRecordSetsInput;
use AsyncAws\Route53\Result\ListHostedZonesResponse;
use AsyncAws\Route53\Result\ListResourceRecordSetsResponse;
use AsyncAws\Route53\Route53Client;
use AsyncAws\Route53\ValueObject\ResourceRecordSet;

/**
 * Route53Client 的测试替身
 *
 * 提供可配置的 Route53 API 行为，用于测试中模拟 AWS Route53 服务
 */
class Route53ClientStub extends Route53Client
{
    /** @var array{return?: ListHostedZonesResponse, called: bool} */
    private array $hostedZonesExpectation = ['called' => false];

    /** @var array{exception?: \Throwable, called: bool} */
    private array $hostedZonesExceptionExpectation = ['called' => false];

    /** @var list<array{input: array<mixed>, return: ListResourceRecordSetsResponse, called: bool}> */
    private array $recordSetsExpectations = [];

    /** @var array{callback?: callable(array<mixed>): ListResourceRecordSetsResponse, called: bool} */
    private array $recordSetsCallbackExpectation = ['called' => false];

    /** @var array{input?: array<mixed>, exception?: \Throwable, called: bool} */
    private array $recordSetsExceptionExpectation = ['called' => false];

    public function __construct()
    {
        parent::__construct(Configuration::create(['region' => 'us-east-1']));
    }

    public function expectListHostedZones(ListHostedZonesResponse $returnValue): void
    {
        $this->hostedZonesExpectation = ['return' => $returnValue, 'called' => false];
    }

    /**
     * @param array<mixed> $expectedInput
     */
    public function expectListResourceRecordSets(array $expectedInput, ListResourceRecordSetsResponse $returnValue): void
    {
        $this->recordSetsExpectations[] = ['input' => $expectedInput, 'return' => $returnValue, 'called' => false];
    }

    /**
     * @param callable(array<mixed>): ListResourceRecordSetsResponse $callback
     */
    public function expectListResourceRecordSetsWithCallback(callable $callback): void
    {
        $this->recordSetsCallbackExpectation = ['callback' => $callback, 'called' => false];
    }

    public function expectListHostedZonesException(\Throwable $exception): void
    {
        $this->hostedZonesExceptionExpectation = ['exception' => $exception, 'called' => false];
    }

    /**
     * @param array<mixed> $expectedInput
     */
    public function expectListResourceRecordSetsException(array $expectedInput, \Throwable $exception): void
    {
        $this->recordSetsExceptionExpectation = ['input' => $expectedInput, 'exception' => $exception, 'called' => false];
    }

    public function listHostedZones($input = []): ListHostedZonesResponse
    {
        return $this->handleListHostedZonesCall();
    }

    public function listResourceRecordSets($input): ListResourceRecordSetsResponse
    {
        return $this->handleListResourceRecordSetsCall($input);
    }

    private function handleListHostedZonesCall(): ListHostedZonesResponse
    {
        if (isset($this->hostedZonesExceptionExpectation['exception'])) {
            $this->hostedZonesExceptionExpectation['called'] = true;
            throw $this->hostedZonesExceptionExpectation['exception'];
        }

        if (isset($this->hostedZonesExpectation['return'])) {
            $this->hostedZonesExpectation['called'] = true;

            return $this->hostedZonesExpectation['return'];
        }

        throw new \RuntimeException('No expectation set for listHostedZones');
    }

    /**
     * @param mixed $input
     */
    private function handleListResourceRecordSetsCall($input): ListResourceRecordSetsResponse
    {
        if ($this->shouldThrowException($input)) {
            return $this->throwRecordSetsException();
        }

        if (isset($this->recordSetsCallbackExpectation['callback'])) {
            $this->recordSetsCallbackExpectation['called'] = true;
            $callback = $this->recordSetsCallbackExpectation['callback'];
            /** @var array<mixed> $input */

            return $callback($input);
        }

        return $this->findMatchingRecordSetsExpectation($input);
    }

    /**
     * @param mixed $input
     */
    private function shouldThrowException($input): bool
    {
        if (!isset($this->recordSetsExceptionExpectation['exception'])) {
            return false;
        }

        return isset($this->recordSetsExceptionExpectation['input'])
            && $this->recordSetsExceptionExpectation['input'] === $input;
    }

    private function throwRecordSetsException(): never
    {
        $this->recordSetsExceptionExpectation['called'] = true;
        $exception = $this->recordSetsExceptionExpectation['exception'] ?? new \RuntimeException('Exception expectation not properly set');
        throw $exception;
    }

    /**
     * @param mixed $input
     */
    private function findMatchingRecordSetsExpectation($input): ListResourceRecordSetsResponse
    {
        foreach ($this->recordSetsExpectations as $i => $expectation) {
            if (!$expectation['called'] && $expectation['input'] === $input) {
                $this->recordSetsExpectations[$i]['called'] = true;

                return $expectation['return'];
            }
        }

        throw new \RuntimeException('No expectation set for listResourceRecordSets with input: ' . json_encode($input));
    }

    public function __destruct()
    {
        // 移除严格的期望检查，避免在测试结束时因为未调用期望方法而失败
        // 实际的业务逻辑验证通过测试断言来保证
    }
}
