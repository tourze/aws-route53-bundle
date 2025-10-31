<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Uid\Uuid;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Repository\AwsAccountRepository;
use Tourze\AwsRoute53Bundle\Tests\AbstractUuidRepositoryTestCase;
use Tourze\AwsRoute53Bundle\Tests\Helper\FixtureLoader;

/**
 * @internal
 *
 * @template-extends AbstractUuidRepositoryTestCase<AwsAccount>
 */
#[CoversClass(AwsAccountRepository::class)]
#[RunTestsInSeparateProcesses]
final class AwsAccountRepositoryTest extends AbstractUuidRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // 手动加载测试数据以确保有数据库记录
        FixtureLoader::loadAwsAccountFixtures(self::getEntityManager());
    }

    protected function createNewEntity(): object
    {
        $account = new AwsAccount();
        $account->setName('Test Account ' . uniqid());
        $account->setCredentialsType('access_key');
        $account->setAccountId(str_pad((string) rand(100000000000, 999999999999), 12, '0', STR_PAD_LEFT));

        return $account;
    }

    /**
     * @return ServiceEntityRepository<AwsAccount>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return self::getService(AwsAccountRepository::class);
    }

    public function testInstanceCreation(): void
    {
        $repository = self::getService(AwsAccountRepository::class);

        $this->assertInstanceOf(AwsAccountRepository::class, $repository);
        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);
    }

    public function testSaveAndFindAwsAccount(): void
    {
        $account = new AwsAccount();
        $account->setName('Test Save Account');
        $account->setCredentialsType('access_key');
        $account->setAccountId('123456789012');

        $repository = self::getService(AwsAccountRepository::class);
        $repository->save($account, true);

        $foundAccount = $repository->find($account->getId());

        $this->assertNotNull($foundAccount);
        $this->assertEquals($account->getId(), $foundAccount->getId());
        $this->assertEquals('Test Save Account', $foundAccount->getName());
        $this->assertEquals('123456789012', $foundAccount->getAccountId());
        $this->assertEquals('access_key', $foundAccount->getCredentialsType());
    }

    public function testFindAccountsWithFilterByName(): void
    {
        $account1 = new AwsAccount();
        $account1->setName('Filter Test Account 1');
        $account1->setCredentialsType('access_key');

        $account2 = new AwsAccount();
        $account2->setName('Filter Test Account 2');
        $account2->setCredentialsType('profile');

        $account3 = new AwsAccount();
        $account3->setName('Other Account');
        $account3->setCredentialsType('access_key');

        $repository = self::getService(AwsAccountRepository::class);
        $repository->save($account1);
        $repository->save($account2);
        $repository->save($account3, true);

        // Test single filter by name
        $results = $repository->findAccountsWithFilter('Filter Test Account 1');
        $foundNames = array_map(fn ($acc) => $acc->getName(), $results);
        $this->assertContains('Filter Test Account 1', $foundNames);
        $this->assertNotContains('Other Account', $foundNames);

        // Test multiple filters
        $results = $repository->findAccountsWithFilter('Filter Test Account 1,Filter Test Account 2');
        $foundNames = array_map(fn ($acc) => $acc->getName(), $results);
        $this->assertContains('Filter Test Account 1', $foundNames);
        $this->assertContains('Filter Test Account 2', $foundNames);
        $this->assertNotContains('Other Account', $foundNames);
    }

    public function testFindAccountsWithFilterByAccountId(): void
    {
        $account1 = new AwsAccount();
        $account1->setName('Account with ID 1');
        $account1->setCredentialsType('access_key');
        $account1->setAccountId('111111111111');

        $account2 = new AwsAccount();
        $account2->setName('Account with ID 2');
        $account2->setCredentialsType('access_key');
        $account2->setAccountId('222222222222');

        $repository = self::getService(AwsAccountRepository::class);
        $repository->save($account1);
        $repository->save($account2, true);

        $results = $repository->findAccountsWithFilter('111111111111');
        $foundAccountIds = array_map(fn ($acc) => $acc->getAccountId(), $results);

        $this->assertContains('111111111111', $foundAccountIds);
        $this->assertNotContains('222222222222', $foundAccountIds);
    }

    public function testFindAccountsWithFilterByUuid(): void
    {
        $account = new AwsAccount();
        $account->setName('UUID Filter Test Account');
        $account->setCredentialsType('access_key');

        $repository = self::getService(AwsAccountRepository::class);
        $repository->save($account, true);

        $accountUuid = (string) $account->getId();
        $results = $repository->findAccountsWithFilter($accountUuid);

        $this->assertCount(1, $results);
        $this->assertEquals($accountUuid, (string) $results[0]->getId());
    }

    public function testFindAccountsWithFilterNoFilter(): void
    {
        $account1 = new AwsAccount();
        $account1->setName('No Filter Account 1');
        $account1->setCredentialsType('access_key');

        $account2 = new AwsAccount();
        $account2->setName('No Filter Account 2');
        $account2->setCredentialsType('profile');

        $repository = self::getService(AwsAccountRepository::class);
        $repository->save($account1);
        $repository->save($account2, true);

        // Test with null filter
        $results = $repository->findAccountsWithFilter(null);
        $this->assertGreaterThanOrEqual(2, count($results));

        // Test with empty string filter
        $results = $repository->findAccountsWithFilter('');
        $this->assertGreaterThanOrEqual(2, count($results));
    }

    public function testFindAccountsWithFilterOrderedByName(): void
    {
        $account1 = new AwsAccount();
        $account1->setName('Z Order Test Account');
        $account1->setCredentialsType('access_key');

        $account2 = new AwsAccount();
        $account2->setName('A Order Test Account');
        $account2->setCredentialsType('access_key');

        $repository = self::getService(AwsAccountRepository::class);
        $repository->save($account1);
        $repository->save($account2, true);

        $results = $repository->findAccountsWithFilter(null);

        // Find our test accounts in the results
        $testAccounts = array_filter($results, fn ($acc) => str_contains($acc->getName(), 'Order Test Account'));
        $names = array_map(fn ($acc) => $acc->getName(), $testAccounts);

        // Should be sorted alphabetically
        $this->assertContains('A Order Test Account', $names);
        $this->assertContains('Z Order Test Account', $names);

        // Check order in full results
        $allNames = array_map(fn ($acc) => $acc->getName(), $results);
        $aIndex = array_search('A Order Test Account', $allNames, true);
        $zIndex = array_search('Z Order Test Account', $allNames, true);

        if (false !== $aIndex && false !== $zIndex) {
            $this->assertLessThan($zIndex, $aIndex);
        }
    }

    public function testFindAccountByIdentifierByName(): void
    {
        $account = new AwsAccount();
        $account->setName('Identifier Test Account');
        $account->setCredentialsType('access_key');
        $account->setAccountId('123123123123');

        $repository = self::getService(AwsAccountRepository::class);
        $repository->save($account, true);

        $foundAccount = $repository->findAccountByIdentifier('Identifier Test Account');

        $this->assertNotNull($foundAccount);
        $this->assertEquals($account->getId(), $foundAccount->getId());
        $this->assertEquals('Identifier Test Account', $foundAccount->getName());
    }

    public function testFindAccountByIdentifierByAccountId(): void
    {
        $account = new AwsAccount();
        $account->setName('Account ID Test Account');
        $account->setCredentialsType('access_key');
        $account->setAccountId('456456456456');

        $repository = self::getService(AwsAccountRepository::class);
        $repository->save($account, true);

        $foundAccount = $repository->findAccountByIdentifier('456456456456');

        $this->assertNotNull($foundAccount);
        $this->assertEquals($account->getId(), $foundAccount->getId());
        $this->assertEquals('456456456456', $foundAccount->getAccountId());
    }

    public function testFindAccountByIdentifierByUuid(): void
    {
        $account = new AwsAccount();
        $account->setName('UUID Test Account');
        $account->setCredentialsType('access_key');

        $repository = self::getService(AwsAccountRepository::class);
        $repository->save($account, true);

        $foundAccount = $repository->findAccountByIdentifier((string) $account->getId());

        $this->assertNotNull($foundAccount);
        $this->assertEquals($account->getId(), $foundAccount->getId());
        $this->assertEquals('UUID Test Account', $foundAccount->getName());
    }

    public function testFindAccountByIdentifierWithValidUuidString(): void
    {
        $account = new AwsAccount();
        $account->setName('Valid UUID String Test');
        $account->setCredentialsType('access_key');

        $repository = self::getService(AwsAccountRepository::class);
        $repository->save($account, true);

        // Test with a valid UUID format string that may not exist as string comparison
        $validUuidString = (string) $account->getId();
        $foundAccount = $repository->findAccountByIdentifier($validUuidString);

        $this->assertNotNull($foundAccount);
        $this->assertEquals($account->getId(), $foundAccount->getId());
    }

    public function testFindAccountByIdentifierReturnsNullForNonExistent(): void
    {
        $repository = self::getService(AwsAccountRepository::class);

        $foundAccount = $repository->findAccountByIdentifier('NonExistentAccount');
        $this->assertNull($foundAccount);

        $foundAccount = $repository->findAccountByIdentifier('999999999999');
        $this->assertNull($foundAccount);

        $foundAccount = $repository->findAccountByIdentifier(Uuid::v6()->toRfc4122());
        $this->assertNull($foundAccount);
    }

    public function testFindEnabledAccounts(): void
    {
        $enabledAccount1 = new AwsAccount();
        $enabledAccount1->setName('Enabled Account 1');
        $enabledAccount1->setCredentialsType('access_key');
        $enabledAccount1->setEnabled(true);

        $enabledAccount2 = new AwsAccount();
        $enabledAccount2->setName('Enabled Account 2');
        $enabledAccount2->setCredentialsType('profile');
        $enabledAccount2->setEnabled(true);

        $disabledAccount = new AwsAccount();
        $disabledAccount->setName('Disabled Account');
        $disabledAccount->setCredentialsType('access_key');
        $disabledAccount->setEnabled(false);

        $repository = self::getService(AwsAccountRepository::class);
        $repository->save($enabledAccount1);
        $repository->save($enabledAccount2);
        $repository->save($disabledAccount, true);

        $enabledAccounts = $repository->findEnabledAccounts();
        $enabledNames = array_map(fn ($acc) => $acc->getName(), $enabledAccounts);

        $this->assertContains('Enabled Account 1', $enabledNames);
        $this->assertContains('Enabled Account 2', $enabledNames);
        $this->assertNotContains('Disabled Account', $enabledNames);

        // Verify all returned accounts are enabled
        foreach ($enabledAccounts as $account) {
            $this->assertTrue($account->isEnabled());
        }
    }

    public function testFindEnabledAccountsOrderedByName(): void
    {
        $account1 = new AwsAccount();
        $account1->setName('Z Enabled Account');
        $account1->setCredentialsType('access_key');
        $account1->setEnabled(true);

        $account2 = new AwsAccount();
        $account2->setName('A Enabled Account');
        $account2->setCredentialsType('access_key');
        $account2->setEnabled(true);

        $repository = self::getService(AwsAccountRepository::class);
        $repository->save($account1);
        $repository->save($account2, true);

        $enabledAccounts = $repository->findEnabledAccounts();

        // Find our test accounts
        $testAccounts = array_filter($enabledAccounts, fn ($acc) => str_contains($acc->getName(), 'Enabled Account'));
        $names = array_map(fn ($acc) => $acc->getName(), $testAccounts);

        $this->assertContains('A Enabled Account', $names);
        $this->assertContains('Z Enabled Account', $names);

        // Check order in full results
        $allNames = array_map(fn ($acc) => $acc->getName(), $enabledAccounts);
        $aIndex = array_search('A Enabled Account', $allNames, true);
        $zIndex = array_search('Z Enabled Account', $allNames, true);

        if (false !== $aIndex && false !== $zIndex) {
            $this->assertLessThan($zIndex, $aIndex);
        }
    }

    public function testSaveWithoutFlush(): void
    {
        $account = new AwsAccount();
        $account->setName('No Flush Account');
        $account->setCredentialsType('access_key');

        $repository = self::getService(AwsAccountRepository::class);
        $repository->save($account, false);

        // Explicitly flush
        self::getEntityManager()->flush();

        // Now it should be findable
        $foundAccount = $repository->find($account->getId());
        $this->assertNotNull($foundAccount);
        $this->assertEquals('No Flush Account', $foundAccount->getName());
    }

    public function testRemoveAccount(): void
    {
        $account = new AwsAccount();
        $account->setName('Removable Account');
        $account->setCredentialsType('access_key');

        $repository = self::getService(AwsAccountRepository::class);
        $repository->save($account, true);

        $accountId = $account->getId();

        $repository->remove($account, true);

        $foundAccount = $repository->find($accountId);
        $this->assertNull($foundAccount);
    }

    public function testRemoveWithoutFlushCustom(): void
    {
        $account = new AwsAccount();
        $account->setName('Remove No Flush Account');
        $account->setCredentialsType('access_key');

        $repository = self::getService(AwsAccountRepository::class);
        $repository->save($account, true);

        $accountId = $account->getId();

        $repository->remove($account, false);

        // Entity should still be findable before flush
        $foundAccount = $repository->find($accountId);
        $this->assertNotNull($foundAccount);

        // Explicitly flush
        self::getEntityManager()->flush();

        // Now it should be removed
        $foundAccount = $repository->find($accountId);
        $this->assertNull($foundAccount);
    }

    public function testAccountEntityProperties(): void
    {
        $account = new AwsAccount();
        $account->setName('Properties Test Account');
        $account->setCredentialsType('assume_role');
        $account->setAccountId('789789789789');
        $account->setPartition('aws-cn');
        $account->setDefaultRegion('cn-north-1');
        $account->setEndpoint('https://route53.cn-north-1.amazonaws.com.cn');
        $account->setCredentialsParams(['role_arn' => 'arn:aws-cn:iam::123456789012:role/TestRole']);
        $account->setTags(['Environment' => 'Test', 'Project' => 'Route53Bundle']);
        $account->setEnabled(false);

        $repository = self::getService(AwsAccountRepository::class);
        $repository->save($account, true);

        $foundAccount = $repository->find($account->getId());

        $this->assertNotNull($foundAccount);
        $this->assertEquals('Properties Test Account', $foundAccount->getName());
        $this->assertEquals('assume_role', $foundAccount->getCredentialsType());
        $this->assertEquals('789789789789', $foundAccount->getAccountId());
        $this->assertEquals('aws-cn', $foundAccount->getPartition());
        $this->assertEquals('cn-north-1', $foundAccount->getDefaultRegion());
        $this->assertEquals('https://route53.cn-north-1.amazonaws.com.cn', $foundAccount->getEndpoint());
        $this->assertEquals(['role_arn' => 'arn:aws-cn:iam::123456789012:role/TestRole'], $foundAccount->getCredentialsParams());
        $this->assertEquals(['Environment' => 'Test', 'Project' => 'Route53Bundle'], $foundAccount->getTags());
        $this->assertFalse($foundAccount->isEnabled());
        $this->assertInstanceOf(\DateTimeImmutable::class, $foundAccount->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $foundAccount->getUpdatedAt());
    }

    public function testAccountToStringMethod(): void
    {
        $account1 = new AwsAccount();
        $account1->setName('String Test Account');
        $account1->setCredentialsType('access_key');

        $this->assertEquals('String Test Account', (string) $account1);

        $account2 = new AwsAccount();
        $account2->setName('String Test Account with ID');
        $account2->setCredentialsType('access_key');
        $account2->setAccountId('123456789012');

        $this->assertEquals('String Test Account with ID (123456789012)', (string) $account2);
    }
}
