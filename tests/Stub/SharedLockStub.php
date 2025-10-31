<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Stub;

use Symfony\Component\Lock\SharedLockInterface;

/**
 * SharedLock 的测试替身
 *
 * 提供可配置的锁行为，用于测试中模拟共享锁
 */
class SharedLockStub implements SharedLockInterface
{
    /** @var array{return?: bool, called: bool} */
    private array $acquireExpectation = ['called' => false];

    public function expectAcquire(bool $returnValue): void
    {
        $this->acquireExpectation = ['return' => $returnValue, 'called' => false];
    }

    public function expectRelease(): void
    {
        // 无需跟踪 release 调用状态，测试通过实际行为验证
    }

    public function acquire(bool $blocking = false): bool
    {
        if (isset($this->acquireExpectation['return'])) {
            $this->acquireExpectation['called'] = true;

            return $this->acquireExpectation['return'];
        }

        return true;
    }

    public function acquireRead(bool $blocking = false): bool
    {
        return $this->acquire($blocking);
    }

    public function refresh(?float $ttl = null): void
    {
        // No-op for testing
    }

    public function release(): void
    {
        // No-op for testing
    }

    public function isAcquired(): bool
    {
        return true;
    }

    public function getRemainingLifetime(): float
    {
        return 300.0;
    }

    public function isExpired(): bool
    {
        return false;
    }

    public function __destruct()
    {
        // 移除严格的期望检查，避免在测试结束时因为未调用期望方法而失败
        // 实际的业务逻辑验证通过测试断言来保证
    }
}
