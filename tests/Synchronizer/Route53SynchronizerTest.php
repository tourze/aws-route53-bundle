<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Synchronizer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\PersistingStoreInterface;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Uid\Uuid;
use Tourze\AwsRoute53Bundle\Contracts\PullSynchronizerInterface;
use Tourze\AwsRoute53Bundle\Contracts\PushSynchronizerInterface;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;
use Tourze\AwsRoute53Bundle\Exception\Route53ClientException;
use Tourze\AwsRoute53Bundle\Exception\Route53ConfigurationException;
use Tourze\AwsRoute53Bundle\Synchronizer\Route53Synchronizer;
use Tourze\AwsRoute53Bundle\Tests\Stub\LockFactoryStub;
use Tourze\AwsRoute53Bundle\Tests\Stub\LoggerStub;
use Tourze\AwsRoute53Bundle\Tests\Stub\SharedLockStub;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(Route53Synchronizer::class)]
#[RunTestsInSeparateProcesses]
final class Route53SynchronizerTest extends AbstractIntegrationTestCase
{
    private Route53Synchronizer $synchronizer;

    private PullSynchronizerInterface $pullSynchronizer;

    private PushSynchronizerInterface $pushSynchronizer;

    private LockFactory $lockFactory;

    private SharedLockInterface $lock;

    protected function onSetUp(): void
    {
        $this->pullSynchronizer = $this->createPullSynchronizer();
        $this->pushSynchronizer = $this->createPushSynchronizer();
        $this->lockFactory = $this->createLockFactory();
        $this->lock = $this->createSharedLock();

        // 直接使用创建的Route53Synchronizer实例进行测试
        // @phpstan-ignore-next-line integrationTest.noDirectInstantiationOfCoveredClass
        $this->synchronizer = new Route53Synchronizer(
            $this->pullSynchronizer,
            $this->pushSynchronizer,
            $this->lockFactory,
            $this->createLogger()
        );
    }

    public function testPullFromRemoteSuccess(): void
    {
        $account = $this->createTestAccount();
        $zone = $this->createTestHostedZone($account);

        $expectedResult = [
            'zones' => 1,
            'records' => 5,
            'changes' => [
                ['action' => 'sync', 'zone' => 'example.com.', 'record' => 'www.example.com. A'],
            ],
        ];

        // 期望调用 pullSynchronizer
        // @phpstan-ignore-next-line method.notFound
        $this->pullSynchronizer->expectPullFromRemote($account, $zone, false, $expectedResult);

        $result = $this->synchronizer->pullFromRemote($account, $zone, false);

        $this->assertSame($expectedResult, $result);
    }

    public function testPullFromRemoteWithDryRun(): void
    {
        $account = $this->createTestAccount();

        $expectedResult = ['zones' => 2, 'records' => 10, 'changes' => []];

        // @phpstan-ignore-next-line method.notFound
        $this->pullSynchronizer->expectPullFromRemote($account, null, true, $expectedResult);

        $result = $this->synchronizer->pullFromRemote($account, null, true);

        $this->assertSame($expectedResult, $result);
    }

    public function testPushToRemoteSuccess(): void
    {
        $account = $this->createTestAccount();
        $zone = $this->createTestHostedZone($account);

        $expectedResult = [
            'changes' => [
                ['action' => 'upsert', 'zone' => 'example.com.', 'record' => 'www.example.com. A'],
            ],
            'errors' => [],
        ];

        // 期望调用 pushSynchronizer
        // @phpstan-ignore-next-line method.notFound
        $this->pushSynchronizer->expectPushToRemote($account, $zone, false, $expectedResult);

        $result = $this->synchronizer->pushToRemote($account, $zone, false);

        $this->assertSame($expectedResult, $result);
    }

    public function testPushToRemoteWithDryRun(): void
    {
        $account = $this->createTestAccount();

        $expectedResult = ['changes' => [], 'errors' => []];

        // @phpstan-ignore-next-line method.notFound
        $this->pushSynchronizer->expectPushToRemote($account, null, true, $expectedResult);

        $result = $this->synchronizer->pushToRemote($account, null, true);

        $this->assertSame($expectedResult, $result);
    }

    public function testBidirectionalSyncWithLockAcquisitionFailed(): void
    {
        $account = $this->createTestAccount();

        // @phpstan-ignore-next-line method.notFound
        $this->lockFactory->expectCreateLock('route53_bidirectional_' . $account->getId()->toString(), 3600, $this->lock);
        // @phpstan-ignore-next-line method.notFound
        $this->lock->expectAcquire(false);

        $this->expectException(Route53ClientException::class);
        $this->expectExceptionMessage('Cannot acquire lock for bidirectional synchronization');

        $this->synchronizer->bidirectionalSync($account);
    }

    public function testBidirectionalSyncLocalWinsMode(): void
    {
        $account = $this->createTestAccount();
        $zone = $this->createTestHostedZone($account);

        $this->setupBidirectionalLockMocks($account, $zone);

        $pullResult = ['zones' => 1, 'records' => 5, 'changes' => []];
        $pushResult = ['changes' => [['action' => 'upsert']], 'errors' => []];

        // 期望记录日志（bidirectional sync 会调用多次）

        // local_wins 模式：先 pull (dry run)，再 push (实际执行)
        // @phpstan-ignore-next-line method.notFound
        $this->pullSynchronizer->expectPullFromRemote($account, $zone, true, $pullResult); // dry run
        // @phpstan-ignore-next-line method.notFound
        $this->pushSynchronizer->expectPushToRemote($account, $zone, false, $pushResult); // 实际执行

        $result = $this->synchronizer->bidirectionalSync($account, $zone, 'local_wins', false);

        $this->assertArrayHasKey('pull', $result);
        $this->assertArrayHasKey('push', $result);
        $this->assertArrayHasKey('conflicts', $result);
        $this->assertArrayHasKey('resolved', $result);
        $this->assertSame($pullResult, $result['pull']);
        $this->assertSame($pushResult, $result['push']);
        $this->assertEmpty($result['conflicts']);
        $this->assertEmpty($result['resolved']);
    }

    public function testBidirectionalSyncRemoteWinsMode(): void
    {
        $account = $this->createTestAccount();

        $this->setupBidirectionalLockMocks($account);

        $pullResult = ['zones' => 2, 'records' => 8, 'changes' => [['action' => 'sync']]];
        $pushResult = ['changes' => [], 'errors' => []];

        // remote_wins 模式：先 push (dry run)，再 pull (实际执行)
        // @phpstan-ignore-next-line method.notFound
        $this->pushSynchronizer->expectPushToRemote($account, null, true, $pushResult); // dry run
        // @phpstan-ignore-next-line method.notFound
        $this->pullSynchronizer->expectPullFromRemote($account, null, false, $pullResult); // 实际执行

        $result = $this->synchronizer->bidirectionalSync($account, null, 'remote_wins', false);

        $this->assertSame($pullResult, $result['pull']);
        $this->assertSame($pushResult, $result['push']);
    }

    public function testBidirectionalSyncMergeMode(): void
    {
        $account = $this->createTestAccount();

        $this->setupBidirectionalLockMocks($account);

        $pullResult = ['zones' => 1, 'records' => 3, 'changes' => []];
        $pushResult = ['changes' => [['action' => 'upsert']], 'errors' => []];

        // merge 模式：都是 dry run
        // @phpstan-ignore-next-line method.notFound
        $this->pullSynchronizer->expectPullFromRemote($account, null, true, $pullResult);
        // @phpstan-ignore-next-line method.notFound
        $this->pushSynchronizer->expectPushToRemote($account, null, true, $pushResult);

        $result = $this->synchronizer->bidirectionalSync($account, null, 'merge', true);

        $this->assertSame($pullResult, $result['pull']);
        $this->assertSame($pushResult, $result['push']);
        $this->assertEmpty($result['conflicts']);
        $this->assertEmpty($result['resolved']);
    }

    public function testBidirectionalSyncWithUnsupportedMode(): void
    {
        $account = $this->createTestAccount();

        $this->setupBidirectionalLockMocks($account);

        $this->expectException(Route53ConfigurationException::class);
        $this->expectExceptionMessage('Unsupported synchronization mode: unsupported_mode');

        $this->synchronizer->bidirectionalSync($account, null, 'unsupported_mode', false);
    }

    public function testBidirectionalSyncWithSpecificZone(): void
    {
        $account = $this->createTestAccount();
        $zone = $this->createTestHostedZone($account);

        // 验证锁键包含 zone ID
        $expectedLockKey = 'route53_bidirectional_' . $account->getId()->toString() . '_' . $zone->getAwsId();

        // @phpstan-ignore-next-line method.notFound
        $this->lockFactory->expectCreateLock($expectedLockKey, 3600, $this->lock);
        // @phpstan-ignore-next-line method.notFound
        $this->lock->expectAcquire(true);
        // @phpstan-ignore-next-line method.notFound
        $this->lock->expectRelease();

        $pullResult = ['zones' => 1, 'records' => 2, 'changes' => []];
        $pushResult = ['changes' => [], 'errors' => []];

        // @phpstan-ignore-next-line method.notFound
        $this->pullSynchronizer->expectPullFromRemote($account, $zone, true, $pullResult);
        // @phpstan-ignore-next-line method.notFound
        $this->pushSynchronizer->expectPushToRemote($account, $zone, false, $pushResult);

        $result = $this->synchronizer->bidirectionalSync($account, $zone, 'local_wins', false);

        $this->assertSame($pullResult, $result['pull']);
        $this->assertSame($pushResult, $result['push']);
    }

    public function testBidirectionalSyncLogsCorrectChangeCounts(): void
    {
        $account = $this->createTestAccount();

        $this->setupBidirectionalLockMocks($account);

        // 模拟有多个变化的结果
        $pullResult = [
            'zones' => 2,
            'records' => 5,
            'changes' => [
                ['action' => 'sync', 'record' => 'www.example.com A'],
                ['action' => 'sync', 'record' => 'mail.example.com MX'],
            ],
        ];
        $pushResult = [
            'changes' => [
                ['action' => 'upsert', 'record' => 'api.example.com A'],
            ],
            'errors' => [],
        ];

        // @phpstan-ignore-next-line method.notFound
        $this->pullSynchronizer->expectPullFromRemote($account, null, true, $pullResult);
        // @phpstan-ignore-next-line method.notFound
        $this->pushSynchronizer->expectPushToRemote($account, null, false, $pushResult);

        $result = $this->synchronizer->bidirectionalSync($account, null, 'local_wins', false);

        // 验证结果包含预期的数据
        $this->assertArrayHasKey('pull', $result);
        $this->assertArrayHasKey('push', $result);
        $this->assertSame($pullResult, $result['pull']);
        $this->assertSame($pushResult, $result['push']);
    }

    private function setupBidirectionalLockMocks(AwsAccount $account, ?HostedZone $zone = null): void
    {
        $expectedLockKey = 'route53_bidirectional_' . $account->getId()->toString() . (null !== $zone ? '_' . $zone->getAwsId() : '');

        // @phpstan-ignore-next-line method.notFound
        $this->lockFactory->expectCreateLock($expectedLockKey, 3600, $this->lock);
        // @phpstan-ignore-next-line method.notFound
        $this->lock->expectAcquire(true);
        // @phpstan-ignore-next-line method.notFound
        $this->lock->expectRelease();
    }

    private function createTestAccount(): AwsAccount
    {
        $account = new AwsAccount();
        $account->setName('test-account');
        $account->setAccountId('123456789012');
        $account->setCredentialsType('profile');
        $account->setCredentialsParams(['profile' => 'default']);
        $account->setDefaultRegion('us-east-1');

        // 使用反射设置UUID，因为构造函数会生成随机UUID
        $reflection = new \ReflectionClass($account);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($account, Uuid::v6());

        return $account;
    }

    private function createTestHostedZone(AwsAccount $account): HostedZone
    {
        $zone = new HostedZone();
        $zone->setAccount($account);
        $zone->setAwsId('Z1234567890');
        $zone->setName('example.com.');
        $zone->setCallerRef('test-ref');
        $zone->setComment('Test zone');
        $zone->setIsPrivate(false);
        $zone->setRrsetCount(0);
        $zone->setLastSyncAt(new \DateTimeImmutable());

        // 使用反射设置UUID
        $reflection = new \ReflectionClass($zone);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($zone, Uuid::v6());

        return $zone;
    }

    private function createPullSynchronizer(): PullSynchronizerInterface
    {
        return new class implements PullSynchronizerInterface {
            /**
             * @phpstan-method expectPullFromRemote(AwsAccount $expectedAccount, ?HostedZone $expectedZone, bool $expectedDryRun, array $returnValue): void
             */
            /** @var list<array{account: AwsAccount, zone: ?HostedZone, dryRun: bool, return: array<string, mixed>, called: bool}> */
            private array $expectations = [];

            /**
             * @param array<string, mixed> $returnValue
             */
            public function expectPullFromRemote(AwsAccount $expectedAccount, ?HostedZone $expectedZone, bool $expectedDryRun, array $returnValue): void
            {
                $this->expectations[] = [
                    'account' => $expectedAccount,
                    'zone' => $expectedZone,
                    'dryRun' => $expectedDryRun,
                    'return' => $returnValue,
                    'called' => false,
                ];
            }

            /**
             * @return array<string, mixed>
             */
            public function pullFromRemote(AwsAccount $account, ?HostedZone $zone = null, bool $dryRun = false): array
            {
                for ($i = 0; $i < count($this->expectations); ++$i) {
                    $expectation = $this->expectations[$i];
                    if (!$expectation['called']
                        && $expectation['account'] === $account
                        && $expectation['zone'] === $zone
                        && $expectation['dryRun'] === $dryRun) {
                        $this->expectations[$i]['called'] = true;

                        return $expectation['return'];
                    }
                }

                throw new \RuntimeException('No expectation set for pullFromRemote');
            }

            public function __destruct()
            {
                foreach ($this->expectations as $expectation) {
                    if (!$expectation['called']) {
                        throw new \RuntimeException('Expected pullFromRemote was not called');
                    }
                }
            }
        };
    }

    private function createPushSynchronizer(): PushSynchronizerInterface
    {
        return new class implements PushSynchronizerInterface {
            /**
             * @phpstan-method expectPushToRemote(AwsAccount $expectedAccount, ?HostedZone $expectedZone, bool $expectedDryRun, array $returnValue): void
             */
            /** @var list<array{account: AwsAccount, zone: ?HostedZone, dryRun: bool, return: array<string, mixed>, called: bool}> */
            private array $expectations = [];

            /**
             * @param array<string, mixed> $returnValue
             */
            public function expectPushToRemote(AwsAccount $expectedAccount, ?HostedZone $expectedZone, bool $expectedDryRun, array $returnValue): void
            {
                $this->expectations[] = [
                    'account' => $expectedAccount,
                    'zone' => $expectedZone,
                    'dryRun' => $expectedDryRun,
                    'return' => $returnValue,
                    'called' => false,
                ];
            }

            /**
             * @return array<string, mixed>
             */
            public function pushToRemote(AwsAccount $account, ?HostedZone $zone = null, bool $dryRun = false): array
            {
                for ($i = 0; $i < count($this->expectations); ++$i) {
                    $expectation = $this->expectations[$i];
                    if (!$expectation['called']
                        && $expectation['account'] === $account
                        && $expectation['zone'] === $zone
                        && $expectation['dryRun'] === $dryRun) {
                        $this->expectations[$i]['called'] = true;

                        return $expectation['return'];
                    }
                }

                throw new \RuntimeException('No expectation set for pushToRemote');
            }

            public function __destruct()
            {
                foreach ($this->expectations as $expectation) {
                    if (!$expectation['called']) {
                        throw new \RuntimeException('Expected pushToRemote was not called');
                    }
                }
            }
        };
    }

    private function createLockFactory(): LockFactoryStub
    {
        return new LockFactoryStub();
    }

    private function createSharedLock(): SharedLockStub
    {
        return new SharedLockStub();
    }

    private function createLogger(): LoggerStub
    {
        return new LoggerStub();
    }
}
