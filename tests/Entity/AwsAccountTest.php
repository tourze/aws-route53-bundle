<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Uid\UuidV6;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(AwsAccount::class)]
final class AwsAccountTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new AwsAccount();
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', 'test-account'];
        yield 'accountId' => ['accountId', '123456789012'];
        yield 'partition' => ['partition', 'aws-cn'];
        yield 'defaultRegion' => ['defaultRegion', 'cn-north-1'];
        yield 'endpoint' => ['endpoint', 'https://custom.endpoint.com'];
        yield 'credentialsType' => ['credentialsType', 'access_key'];
        yield 'credentialsParams' => ['credentialsParams', ['key' => 'value']];
        yield 'tags' => ['tags', ['env' => 'test']];
        yield 'enabled' => ['enabled', false];
    }

    public function testToStringWithAccountId(): void
    {
        $account = new AwsAccount();
        $account->setName('test-account');
        $account->setAccountId('123456789012');

        $this->assertEquals('test-account (123456789012)', (string) $account);
    }

    public function testToStringWithoutAccountId(): void
    {
        $account = new AwsAccount();
        $account->setName('test-account');

        $this->assertEquals('test-account', (string) $account);
    }

    public function testStringableInterface(): void
    {
        $account = new AwsAccount();
        $account->setName('test-account');

        $this->assertInstanceOf(\Stringable::class, $account);
    }

    public function testTouchMethodUpdatesTimestamp(): void
    {
        $account = new AwsAccount();
        $initialUpdatedAt = $account->getUpdatedAt();

        // Sleep to ensure timestamp difference
        usleep(1000);

        // Any setter should trigger touch()
        $account->setName('new-name');

        $this->assertGreaterThan($initialUpdatedAt, $account->getUpdatedAt());
    }

    public function testCreatedAtImmutable(): void
    {
        $account = new AwsAccount();
        $createdAt = $account->getCreatedAt();

        // Sleep and modify account
        usleep(1000);
        $account->setName('test');

        // Created at should not change
        $this->assertEquals($createdAt, $account->getCreatedAt());
    }

    public function testDefaultValues(): void
    {
        $account = new AwsAccount();

        $this->assertEquals('aws', $account->getPartition());
        $this->assertEquals('us-east-1', $account->getDefaultRegion());
        $this->assertTrue($account->isEnabled());
        $this->assertNull($account->getAccountId());
        $this->assertNull($account->getEndpoint());
        $this->assertNull($account->getTags());
        $this->assertEquals([], $account->getCredentialsParams());
    }
}
