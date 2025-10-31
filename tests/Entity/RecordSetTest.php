<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Uid\UuidV6;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;
use Tourze\AwsRoute53Bundle\Entity\RecordSet;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(RecordSet::class)]
final class RecordSetTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new RecordSet();
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $account = new AwsAccount();
        $account->setName('test-account');

        $zone = new HostedZone();
        $zone->setAccount($account);
        $zone->setAwsId('/hostedzone/Z1234567890');
        $zone->setName('example.com.');

        yield 'zone' => ['zone', $zone];
        yield 'name' => ['name', 'www.example.com'];
        yield 'type' => ['type', 'A'];
        yield 'ttl' => ['ttl', 300];
        yield 'aliasTarget' => ['aliasTarget', ['DNSName' => 'example.com']];
        yield 'resourceRecords' => ['resourceRecords', [['Value' => '192.0.2.1']]];
        yield 'routingPolicy' => ['routingPolicy', ['type' => 'weighted', 'weight' => 100]];
        yield 'healthCheckId' => ['healthCheckId', 'health-check-123'];
        yield 'setIdentifier' => ['setIdentifier', 'set-1'];
        yield 'region' => ['region', 'us-east-1'];
        yield 'geoLocation' => ['geoLocation', ['CountryCode' => 'US']];
        yield 'multiValueAnswer' => ['multiValueAnswer', true];
        yield 'localFingerprint' => ['localFingerprint', 'local-123'];
        yield 'remoteFingerprint' => ['remoteFingerprint', 'remote-123'];
        yield 'lastChangeInfoId' => ['lastChangeInfoId', 'change-123'];
        yield 'managedBySystem' => ['managedBySystem', true];
        yield 'protected' => ['protected', true];
    }

    public function testToString(): void
    {
        $record = new RecordSet();
        $record->setName('www.example.com.');
        $record->setType('A');

        $this->assertEquals('www.example.com. (A)', (string) $record);
    }

    public function testStringableInterface(): void
    {
        $record = new RecordSet();
        $record->setName('www.example.com.');
        $record->setType('A');

        $this->assertInstanceOf(\Stringable::class, $record);
    }

    public function testTouchMethodUpdatesTimestamp(): void
    {
        $record = new RecordSet();
        $initialUpdatedAt = $record->getUpdatedAt();
        $initialLastLocalModified = $record->getLastLocalModifiedAt();

        // Sleep to ensure timestamp difference
        usleep(1000);

        // Any setter should trigger touch()
        $record->setName('test.example.com.');

        $this->assertGreaterThan($initialUpdatedAt, $record->getUpdatedAt());
        $this->assertNotEquals($initialLastLocalModified, $record->getLastLocalModifiedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $record->getLastLocalModifiedAt());
    }

    public function testCreatedAtImmutable(): void
    {
        $record = new RecordSet();
        $createdAt = $record->getCreatedAt();

        // Sleep and modify record
        usleep(1000);
        $record->setName('test.example.com.');

        // Created at should not change
        $this->assertEquals($createdAt, $record->getCreatedAt());
    }

    public function testDefaultValues(): void
    {
        $record = new RecordSet();

        $this->assertFalse($record->isManagedBySystem());
        $this->assertFalse($record->isProtected());
        $this->assertNull($record->getTtl());
        $this->assertNull($record->getAliasTarget());
        $this->assertNull($record->getResourceRecords());
        $this->assertNull($record->getRoutingPolicy());
        $this->assertNull($record->getHealthCheckId());
        $this->assertNull($record->getSetIdentifier());
        $this->assertNull($record->getRegion());
        $this->assertNull($record->getGeoLocation());
        $this->assertNull($record->isMultiValueAnswer());
        $this->assertNull($record->getLocalFingerprint());
        $this->assertNull($record->getRemoteFingerprint());
        $this->assertNull($record->getLastLocalModifiedAt());
        $this->assertNull($record->getLastSeenRemoteAt());
        $this->assertNull($record->getLastChangeInfoId());
    }

    public function testNullableValues(): void
    {
        $record = new RecordSet();

        // Set values then reset to null
        $record->setTtl(300);
        $record->setTtl(null);
        $this->assertNull($record->getTtl());

        $record->setAliasTarget(['test' => 'value']);
        $record->setAliasTarget(null);
        $this->assertNull($record->getAliasTarget());

        $record->setResourceRecords(['value' => 'test']);
        $record->setResourceRecords(null);
        $this->assertNull($record->getResourceRecords());

        $record->setRoutingPolicy(['type' => 'test']);
        $record->setRoutingPolicy(null);
        $this->assertNull($record->getRoutingPolicy());

        $record->setHealthCheckId('test');
        $record->setHealthCheckId(null);
        $this->assertNull($record->getHealthCheckId());

        $record->setSetIdentifier('test');
        $record->setSetIdentifier(null);
        $this->assertNull($record->getSetIdentifier());

        $record->setRegion('us-east-1');
        $record->setRegion(null);
        $this->assertNull($record->getRegion());

        $record->setGeoLocation(['country' => 'test']);
        $record->setGeoLocation(null);
        $this->assertNull($record->getGeoLocation());

        $record->setMultiValueAnswer(true);
        $record->setMultiValueAnswer(null);
        $this->assertNull($record->isMultiValueAnswer());

        $record->setLocalFingerprint('test');
        $record->setLocalFingerprint(null);
        $this->assertNull($record->getLocalFingerprint());

        $record->setRemoteFingerprint('test');
        $record->setRemoteFingerprint(null);
        $this->assertNull($record->getRemoteFingerprint());

        $now = new \DateTimeImmutable();
        $record->setLastLocalModifiedAt($now);
        $this->assertInstanceOf(\DateTimeImmutable::class, $record->getLastLocalModifiedAt());

        // Note: Cannot set lastLocalModifiedAt to null because touch() always sets it

        $record->setLastSeenRemoteAt($now);
        $record->setLastSeenRemoteAt(null);
        $this->assertNull($record->getLastSeenRemoteAt());

        $record->setLastChangeInfoId('test');
        $record->setLastChangeInfoId(null);
        $this->assertNull($record->getLastChangeInfoId());
    }
}
