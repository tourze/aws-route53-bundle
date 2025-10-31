<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Uid\UuidV6;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(HostedZone::class)]
final class HostedZoneTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new HostedZone();
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $account = new AwsAccount();
        $account->setName('test-account');

        yield 'account' => ['account', $account];
        yield 'awsId' => ['awsId', '/hostedzone/Z1234567890'];
        yield 'name' => ['name', 'example.com.'];
        yield 'callerRef' => ['callerRef', 'test-caller-ref'];
        yield 'comment' => ['comment', 'Test hosted zone'];
        yield 'tags' => ['tags', ['env' => 'test']];
        yield 'vpcAssociations' => ['vpcAssociations', [['vpcId' => 'vpc-123']]];
        yield 'rrsetCount' => ['rrsetCount', 10];
        yield 'sourceOfTruth' => ['sourceOfTruth', 'remote'];
        yield 'remoteFingerprint' => ['remoteFingerprint', 'abc123'];
    }

    public function testToString(): void
    {
        $zone = new HostedZone();
        $zone->setName('example.com.');

        $this->assertEquals('example.com.', (string) $zone);
    }

    public function testStringableInterface(): void
    {
        $zone = new HostedZone();
        $zone->setName('example.com.');

        $this->assertInstanceOf(\Stringable::class, $zone);
    }

    public function testTouchMethodUpdatesTimestamp(): void
    {
        $zone = new HostedZone();
        $initialUpdatedAt = $zone->getUpdatedAt();

        // Sleep to ensure timestamp difference
        usleep(1000);

        // Any setter should trigger touch()
        $zone->setName('test.com.');

        $this->assertGreaterThan($initialUpdatedAt, $zone->getUpdatedAt());
    }

    public function testCreatedAtImmutable(): void
    {
        $zone = new HostedZone();
        $createdAt = $zone->getCreatedAt();

        // Sleep and modify zone
        usleep(1000);
        $zone->setName('test.com.');

        // Created at should not change
        $this->assertEquals($createdAt, $zone->getCreatedAt());
    }

    public function testDefaultValues(): void
    {
        $zone = new HostedZone();

        $this->assertFalse($zone->isPrivate());
        $this->assertEquals('local', $zone->getSourceOfTruth());
        $this->assertNull($zone->getCallerRef());
        $this->assertNull($zone->getComment());
        $this->assertNull($zone->getTags());
        $this->assertNull($zone->getVpcAssociations());
        $this->assertNull($zone->getRrsetCount());
        $this->assertNull($zone->getRemoteFingerprint());
        $this->assertNull($zone->getLastSyncAt());
    }

    public function testSetLastSyncAtNull(): void
    {
        $zone = new HostedZone();
        $now = new \DateTimeImmutable();

        $zone->setLastSyncAt($now);
        $this->assertEquals($now, $zone->getLastSyncAt());

        $zone->setLastSyncAt(null);
        $this->assertNull($zone->getLastSyncAt());
    }
}
