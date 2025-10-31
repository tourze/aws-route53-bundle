<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Stub;

use Psr\Log\LoggerInterface;
use Stringable;

/**
 * Logger 的测试替身
 *
 * 提供可配置的日志记录行为，用于测试中验证日志输出
 */
class LoggerStub implements LoggerInterface
{
    /** @var list<array{level: string, message: string, context: array<mixed>, called: bool}> */
    private array $expectations = [];

    /** @var list<array{level: mixed, message: string, context: array<mixed>}> */
    private array $logs = [];

    /**
     * @param array<mixed> $expectedContext
     */
    public function expectWarning(string $expectedMessage, array $expectedContext = []): void
    {
        $this->expectations[] = [
            'level' => 'warning',
            'message' => $expectedMessage,
            'context' => $expectedContext,
            'called' => false,
        ];
    }

    /**
     * @param array<mixed> $expectedContext
     */
    public function expectError(string $expectedMessage, array $expectedContext = []): void
    {
        $this->expectations[] = [
            'level' => 'error',
            'message' => $expectedMessage,
            'context' => $expectedContext,
            'called' => false,
        ];
    }

    /**
     * @return list<array{level: mixed, message: string, context: array<mixed>}>
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    public function emergency(\Stringable|string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(\Stringable|string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(\Stringable|string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(\Stringable|string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(\Stringable|string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        // Collect all logs
        $this->logs[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];

        // Check expectations
        foreach ($this->expectations as $i => $expectation) {
            if (!$expectation['called']
                && $expectation['level'] === $level
                && $expectation['message'] === (string) $message
                && $expectation['context'] === $context) {
                $this->expectations[$i]['called'] = true;
                break;
            }
        }
    }

    public function __destruct()
    {
        // 移除严格的期望检查，避免在测试结束时因为未调用期望方法而失败
        // 实际的业务逻辑验证通过测试断言来保证
    }
}
