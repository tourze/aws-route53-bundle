<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Uid\UuidV6;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Entity\ChangeLog;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(ChangeLog::class)]
final class ChangeLogTest extends AbstractEntityTestCase
{
    public function testConstruct(): void
    {
        $changeLog = new ChangeLog();

        $this->assertInstanceOf(UuidV6::class, $changeLog->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $changeLog->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $changeLog->getUpdatedAt());
        $this->assertSame('pending', $changeLog->getStatus());
        $this->assertNull($changeLog->getZone());
        $this->assertNull($changeLog->getBefore());
        $this->assertNull($changeLog->getAfter());
        $this->assertNull($changeLog->getPlanId());
        $this->assertNull($changeLog->getAppliedAt());
        $this->assertNull($changeLog->getAwsChangeId());
        $this->assertNull($changeLog->getError());
    }

    public function testAccountProperty(): void
    {
        $account = $this->createTestAccount();
        $changeLog = new ChangeLog();

        $originalUpdatedAt = $changeLog->getUpdatedAt();

        sleep(1);
        $changeLog->setAccount($account);

        $this->assertSame($account, $changeLog->getAccount());
        $this->assertGreaterThan($originalUpdatedAt, $changeLog->getUpdatedAt());
    }

    public function testZoneProperty(): void
    {
        $account = $this->createTestAccount();
        $zone = $this->createTestHostedZone($account);
        $changeLog = new ChangeLog();

        $originalUpdatedAt = $changeLog->getUpdatedAt();

        sleep(1);
        $changeLog->setZone($zone);

        $this->assertSame($zone, $changeLog->getZone());
        $this->assertGreaterThan($originalUpdatedAt, $changeLog->getUpdatedAt());

        $changeLog->setZone(null);
        $this->assertNull($changeLog->getZone());
    }

    public function testRecordKeyProperty(): void
    {
        $changeLog = new ChangeLog();
        $recordKey = 'test.example.com A';

        $originalUpdatedAt = $changeLog->getUpdatedAt();

        sleep(1);
        $changeLog->setRecordKey($recordKey);

        $this->assertSame($recordKey, $changeLog->getRecordKey());
        $this->assertGreaterThan($originalUpdatedAt, $changeLog->getUpdatedAt());
    }

    public function testActionProperty(): void
    {
        $changeLog = new ChangeLog();
        $action = 'CREATE';

        $originalUpdatedAt = $changeLog->getUpdatedAt();

        sleep(1);
        $changeLog->setAction($action);

        $this->assertSame($action, $changeLog->getAction());
        $this->assertGreaterThan($originalUpdatedAt, $changeLog->getUpdatedAt());
    }

    public function testBeforeProperty(): void
    {
        $changeLog = new ChangeLog();
        $before = ['type' => 'A', 'value' => '192.168.1.1'];

        $originalUpdatedAt = $changeLog->getUpdatedAt();

        sleep(1);
        $changeLog->setBefore($before);

        $this->assertSame($before, $changeLog->getBefore());
        $this->assertGreaterThan($originalUpdatedAt, $changeLog->getUpdatedAt());

        $changeLog->setBefore(null);
        $this->assertNull($changeLog->getBefore());
    }

    public function testAfterProperty(): void
    {
        $changeLog = new ChangeLog();
        $after = ['type' => 'A', 'value' => '192.168.1.2'];

        $originalUpdatedAt = $changeLog->getUpdatedAt();

        sleep(1);
        $changeLog->setAfter($after);

        $this->assertSame($after, $changeLog->getAfter());
        $this->assertGreaterThan($originalUpdatedAt, $changeLog->getUpdatedAt());

        $changeLog->setAfter(null);
        $this->assertNull($changeLog->getAfter());
    }

    public function testPlanIdProperty(): void
    {
        $changeLog = new ChangeLog();
        $planId = 'plan-123456';

        $originalUpdatedAt = $changeLog->getUpdatedAt();

        sleep(1);
        $changeLog->setPlanId($planId);

        $this->assertSame($planId, $changeLog->getPlanId());
        $this->assertGreaterThan($originalUpdatedAt, $changeLog->getUpdatedAt());

        $changeLog->setPlanId(null);
        $this->assertNull($changeLog->getPlanId());
    }

    public function testAppliedAtProperty(): void
    {
        $changeLog = new ChangeLog();
        $appliedAt = new \DateTimeImmutable('2023-01-01 12:00:00');

        $originalUpdatedAt = $changeLog->getUpdatedAt();

        sleep(1);
        $changeLog->setAppliedAt($appliedAt);

        $this->assertSame($appliedAt, $changeLog->getAppliedAt());
        $this->assertGreaterThan($originalUpdatedAt, $changeLog->getUpdatedAt());

        $changeLog->setAppliedAt(null);
        $this->assertNull($changeLog->getAppliedAt());
    }

    public function testAwsChangeIdProperty(): void
    {
        $changeLog = new ChangeLog();
        $awsChangeId = 'C12345678901234567890123456789';

        $originalUpdatedAt = $changeLog->getUpdatedAt();

        sleep(1);
        $changeLog->setAwsChangeId($awsChangeId);

        $this->assertSame($awsChangeId, $changeLog->getAwsChangeId());
        $this->assertGreaterThan($originalUpdatedAt, $changeLog->getUpdatedAt());

        $changeLog->setAwsChangeId(null);
        $this->assertNull($changeLog->getAwsChangeId());
    }

    public function testStatusProperty(): void
    {
        $changeLog = new ChangeLog();
        $status = 'applied';

        $originalUpdatedAt = $changeLog->getUpdatedAt();

        sleep(1);
        $changeLog->setStatus($status);

        $this->assertSame($status, $changeLog->getStatus());
        $this->assertGreaterThan($originalUpdatedAt, $changeLog->getUpdatedAt());
    }

    public function testErrorProperty(): void
    {
        $changeLog = new ChangeLog();
        $error = 'Failed to apply change';

        $originalUpdatedAt = $changeLog->getUpdatedAt();

        sleep(1);
        $changeLog->setError($error);

        $this->assertSame($error, $changeLog->getError());
        $this->assertGreaterThan($originalUpdatedAt, $changeLog->getUpdatedAt());

        $changeLog->setError(null);
        $this->assertNull($changeLog->getError());
    }

    public function testToString(): void
    {
        $account = $this->createTestAccount();
        $changeLog = new ChangeLog();
        $changeLog->setAccount($account);
        $changeLog->setRecordKey('test.example.com A');
        $changeLog->setAction('CREATE');

        $this->assertSame('test.example.com A (CREATE)', (string) $changeLog);
    }

    private function createTestAccount(): AwsAccount
    {
        $account = new AwsAccount();
        $account->setName('test-account');
        $account->setAccountId('123456789012');
        $account->setCredentialsType('profile');
        $account->setCredentialsParams(['profile' => 'default']);
        $account->setDefaultRegion('us-east-1');

        return $account;
    }

    private function createTestHostedZone(AwsAccount $account): HostedZone
    {
        $zone = new HostedZone();
        $zone->setAccount($account);
        $zone->setName('example.com.');
        $zone->setAwsId('Z123456789');
        $zone->setComment('Test zone');

        return $zone;
    }

    protected function createEntity(): ChangeLog
    {
        $account = $this->createTestAccount();
        $changeLog = new ChangeLog();
        $changeLog->setAccount($account);
        $changeLog->setRecordKey('test.example.com A');
        $changeLog->setAction('CREATE');

        return $changeLog;
    }

    /**
     * 提供属性及其样本值的 Data Provider.
     *
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'recordKey' => ['recordKey', 'test.example.com A'];
        yield 'action' => ['action', 'CREATE'];
        yield 'before' => ['before', ['type' => 'A', 'value' => '192.168.1.1']];
        yield 'after' => ['after', ['type' => 'A', 'value' => '192.168.1.2']];
        yield 'planId' => ['planId', 'plan-123456'];
        yield 'appliedAt' => ['appliedAt', new \DateTimeImmutable('2023-01-01 12:00:00')];
        yield 'awsChangeId' => ['awsChangeId', 'C12345678901234567890123456789'];
        yield 'status' => ['status', 'applied'];
        yield 'error' => ['error', 'Failed to apply change'];
    }
}
