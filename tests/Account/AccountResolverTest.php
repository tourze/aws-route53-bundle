<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Account;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Uid\Uuid;
use Tourze\AwsRoute53Bundle\Account\AccountResolver;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AccountResolver::class)]
#[RunTestsInSeparateProcesses]
final class AccountResolverTest extends AbstractIntegrationTestCase
{
    private AccountResolver $accountResolver;

    protected function onSetUp(): void
    {
        $this->accountResolver = self::getService(AccountResolver::class);

        // 清空数据库中可能存在的DataFixtures数据，确保测试隔离性
        // 必须在开始事务之前清空，因为事务会回滚所有操作
        $em = self::getEntityManager();
        $existingAccounts = $em->getRepository(AwsAccount::class)->findAll();
        foreach ($existingAccounts as $account) {
            $em->remove($account);
        }
        $em->flush();

        // 清空后再开始事务
        self::getEntityManager()->beginTransaction();
    }

    protected function onTearDown(): void
    {
        self::getEntityManager()->rollback();
    }

    public function testResolveAccountsWithoutFilter(): void
    {
        // 准备测试数据
        $account1 = $this->createTestAccount('test-account-1', '123456789012');
        $account2 = $this->createTestAccount('test-account-2', '123456789013');
        $account3 = $this->createTestAccount('test-account-3', null);

        // 禁用一个账户
        $disabledAccount = $this->createTestAccount('disabled-account', '123456789014');
        $disabledAccount->setEnabled(false);

        self::getEntityManager()->flush();

        // 测试获取所有账户（包括禁用的）
        $accounts = $this->accountResolver->resolveAccounts();

        $this->assertCount(4, $accounts);

        // 验证排序（按名称升序）
        $this->assertSame('disabled-account', $accounts[0]->getName());
        $this->assertSame('test-account-1', $accounts[1]->getName());
        $this->assertSame('test-account-2', $accounts[2]->getName());
        $this->assertSame('test-account-3', $accounts[3]->getName());
    }

    public function testResolveAccountsWithNameFilter(): void
    {
        // 准备测试数据
        $account1 = $this->createTestAccount('production-account', '123456789012');
        $account2 = $this->createTestAccount('staging-account', '123456789013');
        $account3 = $this->createTestAccount('development-account', '123456789014');

        self::getEntityManager()->flush();

        // 测试按名称过滤
        $accounts = $this->accountResolver->resolveAccounts('production-account');

        $this->assertCount(1, $accounts);
        $this->assertSame('production-account', $accounts[0]->getName());
    }

    public function testResolveAccountsWithAccountIdFilter(): void
    {
        // 准备测试数据
        $account1 = $this->createTestAccount('production-account', '123456789012');
        $account2 = $this->createTestAccount('staging-account', '123456789013');

        self::getEntityManager()->flush();

        // 测试按 account_id 过滤
        $accounts = $this->accountResolver->resolveAccounts('123456789013');

        $this->assertCount(1, $accounts);
        $this->assertSame('staging-account', $accounts[0]->getName());
        $this->assertSame('123456789013', $accounts[0]->getAccountId());
    }

    public function testResolveAccountsWithUuidFilter(): void
    {
        // 准备测试数据
        $account1 = $this->createTestAccount('production-account', '123456789012');
        $account2 = $this->createTestAccount('staging-account', '123456789013');

        self::getEntityManager()->flush();

        // 获取第一个账户的UUID
        $accountUuid = (string) $account1->getId();

        // 测试按UUID过滤
        $accounts = $this->accountResolver->resolveAccounts($accountUuid);

        $this->assertCount(1, $accounts);
        $this->assertSame((string) $account1->getId(), (string) $accounts[0]->getId());
        $this->assertSame('production-account', $accounts[0]->getName());
    }

    public function testResolveAccountsWithMultipleFilters(): void
    {
        // 准备测试数据
        $account1 = $this->createTestAccount('production-account', '123456789012');
        $account2 = $this->createTestAccount('staging-account', '123456789013');
        $account3 = $this->createTestAccount('development-account', '123456789014');

        self::getEntityManager()->flush();

        // 测试多个过滤条件（逗号分隔）
        $accounts = $this->accountResolver->resolveAccounts('production-account,123456789013');

        $this->assertCount(2, $accounts);
        $accountNames = array_map(fn ($account) => $account->getName(), $accounts);
        $this->assertContains('production-account', $accountNames);
        $this->assertContains('staging-account', $accountNames);
    }

    public function testResolveAccountsWithEmptyFilter(): void
    {
        // 准备测试数据
        $account1 = $this->createTestAccount('test-account', '123456789012');
        self::getEntityManager()->flush();

        // 测试空过滤器
        $accounts = $this->accountResolver->resolveAccounts('');

        $this->assertCount(1, $accounts);
        $this->assertSame('test-account', $accounts[0]->getName());
    }

    public function testResolveAccountByName(): void
    {
        // 准备测试数据
        $account1 = $this->createTestAccount('production-account', '123456789012');
        $account2 = $this->createTestAccount('staging-account', '123456789013');

        self::getEntityManager()->flush();

        // 测试按名称解析单个账户
        $account = $this->accountResolver->resolveAccount('production-account');

        $this->assertNotNull($account);
        $this->assertSame('production-account', $account->getName());
        $this->assertSame('123456789012', $account->getAccountId());
    }

    public function testResolveAccountByAccountId(): void
    {
        // 准备测试数据
        $account1 = $this->createTestAccount('production-account', '123456789012');

        self::getEntityManager()->flush();

        // 测试按 account_id 解析单个账户
        $account = $this->accountResolver->resolveAccount('123456789012');

        $this->assertNotNull($account);
        $this->assertSame('production-account', $account->getName());
        $this->assertSame('123456789012', $account->getAccountId());
    }

    public function testResolveAccountByUuidString(): void
    {
        // 准备测试数据
        $account1 = $this->createTestAccount('production-account', '123456789012');

        self::getEntityManager()->flush();

        // 获取账户的UUID字符串
        $accountUuid = (string) $account1->getId();

        // 测试按UUID解析单个账户
        $account = $this->accountResolver->resolveAccount($accountUuid);

        $this->assertNotNull($account);
        $this->assertSame((string) $account1->getId(), (string) $account->getId());
        $this->assertSame('production-account', $account->getName());
    }

    public function testResolveAccountByValidUuidButNotExists(): void
    {
        // 生成一个有效的UUID但数据库中不存在的账户
        $nonExistentUuid = Uuid::v6()->toRfc4122();

        // 测试查询不存在的UUID
        $account = $this->accountResolver->resolveAccount($nonExistentUuid);

        $this->assertNull($account);
    }

    public function testResolveAccountNotExists(): void
    {
        // 准备测试数据
        $this->createTestAccount('production-account', '123456789012');
        self::getEntityManager()->flush();

        // 测试不存在的账户
        $account = $this->accountResolver->resolveAccount('non-existent-account');

        $this->assertNull($account);
    }

    public function testResolveAccountWithFallbackUuidQuery(): void
    {
        // 准备测试数据
        $account1 = $this->createTestAccount('production-account', '123456789012');

        self::getEntityManager()->flush();

        // 获取账户的UUID字符串
        $accountUuid = (string) $account1->getId();

        // 测试fallback UUID查询机制
        // findAccountByIdentifier方法会先尝试字符串查询，然后回退到UUID对象查询
        $account = $this->accountResolver->resolveAccount($accountUuid);

        $this->assertNotNull($account);
        $this->assertSame((string) $account1->getId(), (string) $account->getId());
        $this->assertSame('production-account', $account->getName());
    }

    public function testGetEnabledAccounts(): void
    {
        // 准备测试数据
        $enabledAccount1 = $this->createTestAccount('enabled-account-1', '123456789012');
        $enabledAccount2 = $this->createTestAccount('enabled-account-2', '123456789013');

        $disabledAccount1 = $this->createTestAccount('disabled-account-1', '123456789014');
        $disabledAccount1->setEnabled(false);

        $disabledAccount2 = $this->createTestAccount('disabled-account-2', '123456789015');
        $disabledAccount2->setEnabled(false);

        self::getEntityManager()->flush();

        // 测试只获取启用的账户
        $accounts = $this->accountResolver->getEnabledAccounts();

        $this->assertCount(2, $accounts);

        // 验证都是启用状态
        foreach ($accounts as $account) {
            $this->assertTrue($account->isEnabled());
        }

        // 验证排序（按名称升序）
        $this->assertSame('enabled-account-1', $accounts[0]->getName());
        $this->assertSame('enabled-account-2', $accounts[1]->getName());
    }

    public function testGetEnabledAccountsWhenNoEnabledAccounts(): void
    {
        // 准备测试数据 - 只有禁用的账户
        $disabledAccount = $this->createTestAccount('disabled-account', '123456789012');
        $disabledAccount->setEnabled(false);

        self::getEntityManager()->flush();

        // 测试没有启用账户的情况
        $accounts = $this->accountResolver->getEnabledAccounts();

        $this->assertEmpty($accounts);
    }

    public function testGetEnabledAccountsWithNoAccounts(): void
    {
        // 不创建任何账户

        // 测试没有任何账户的情况
        $accounts = $this->accountResolver->getEnabledAccounts();

        $this->assertEmpty($accounts);
    }

    private function createTestAccount(string $name, ?string $accountId): AwsAccount
    {
        $account = new AwsAccount();
        $account->setName($name);
        $account->setAccountId($accountId);
        $account->setCredentialsType('profile');
        $account->setCredentialsParams(['profile' => 'default']);
        $account->setDefaultRegion('us-east-1');

        self::getEntityManager()->persist($account);

        return $account;
    }
}
