<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Helper;

use AsyncAws\Route53\Route53Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\PersistingStoreInterface;
use Symfony\Component\Lock\SharedLockInterface;
use Tourze\AwsRoute53Bundle\Contracts\Route53ClientFactoryInterface;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;

/**
 * 测试辅助类，用于简化复杂的匿名类创建
 */
class TestHelper
{
    /**
     * 创建简单的 Route53ClientFactory 实现
     */
    public static function createRoute53ClientFactory(Route53Client $client): Route53ClientFactoryInterface
    {
        return new class($client) implements Route53ClientFactoryInterface {
            private Route53Client $client;

            public function __construct(Route53Client $client)
            {
                $this->client = $client;
            }

            public function createClient(AwsAccount $account): Route53Client
            {
                return $this->client;
            }

            public function getOrCreateClient(AwsAccount $account): Route53Client
            {
                return $this->client;
            }

            public function clearCache(?AwsAccount $account = null): void
            {
                // No-op for testing
            }
        };
    }

    /**
     * 创建简单的 LockFactory 实现
     */
    public static function createLockFactory(SharedLockInterface $lock): LockFactory
    {
        return new class($lock) extends LockFactory {
            private ?SharedLockInterface $lockToReturn = null;

            public function __construct(SharedLockInterface $lock)
            {
                $this->lockToReturn = $lock;
                // LockFactory 需要一个 PersistingStoreInterface 实例，我们使用空的实现
                parent::__construct(new class implements PersistingStoreInterface {
                    public function save(Key $key): void
                    {
                        // No-op for testing
                    }

                    public function putOffExpiration(Key $key, float $ttl): void
                    {
                        // No-op for testing
                    }

                    public function delete(Key $key): void
                    {
                        // No-op for testing
                    }

                    public function exists(Key $key): bool
                    {
                        return false;
                    }
                });
            }

            public function createLock(string $resource, $ttl = 300, $autoRelease = true): SharedLockInterface
            {
                return $this->lockToReturn ?? throw new \RuntimeException('No lock set');
            }
        };
    }

    /**
     * 创建简单的 Logger 实现
     */
    public static function createLogger(): LoggerInterface
    {
        return new class implements LoggerInterface {
            public function emergency(string|\Stringable $message, array $context = []): void
            {
                /* No-op */
            }

            public function alert(string|\Stringable $message, array $context = []): void
            {
                /* No-op */
            }

            public function critical(string|\Stringable $message, array $context = []): void
            {
                /* No-op */
            }

            public function error(string|\Stringable $message, array $context = []): void
            {
                /* No-op */
            }

            public function warning(string|\Stringable $message, array $context = []): void
            {
                /* No-op */
            }

            public function notice(string|\Stringable $message, array $context = []): void
            {
                /* No-op */
            }

            public function info(string|\Stringable $message, array $context = []): void
            {
                /* No-op */
            }

            public function debug(string|\Stringable $message, array $context = []): void
            {
                /* No-op */
            }

            public function log(mixed $level, string|\Stringable $message, array $context = []): void
            {
                /* No-op */
            }
        };
    }

    /**
     * 创建简单的 SharedLock 实现
     */
    public static function createSharedLock(bool $acquireResult = true): SharedLockInterface
    {
        return new class($acquireResult) implements SharedLockInterface {
            private bool $acquireResult;

            public function __construct(bool $acquireResult)
            {
                $this->acquireResult = $acquireResult;
            }

            public function acquire(bool $blocking = false): bool
            {
                return $this->acquireResult;
            }

            public function acquireRead(bool $blocking = false): bool
            {
                return $this->acquireResult;
            }

            public function refresh(?float $ttl = null): void
            {
                /* No-op */
            }

            public function release(): void
            {
                /* No-op */
            }

            public function isAcquired(): bool
            {
                return $this->acquireResult;
            }

            public function getRemainingLifetime(): float
            {
                return 300.0;
            }

            public function isExpired(): bool
            {
                return false;
            }
        };
    }
}
