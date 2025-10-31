<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;
use Tourze\AwsRoute53Bundle\Repository\HostedZoneRepository;
use Tourze\AwsRoute53Bundle\Tests\AbstractUuidRepositoryTestCase;
use Tourze\AwsRoute53Bundle\Tests\Helper\FixtureLoader;

/**
 * @internal
 *
 * @template-extends AbstractUuidRepositoryTestCase<HostedZone>
 */
#[CoversClass(HostedZoneRepository::class)]
#[RunTestsInSeparateProcesses]
final class HostedZoneRepositoryTest extends AbstractUuidRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // 手动加载测试数据以确保有数据库记录
        FixtureLoader::loadHostedZoneFixtures(self::getEntityManager());
    }

    protected function createNewEntity(): object
    {
        $account = new AwsAccount();
        $account->setName('Test Account');
        $account->setCredentialsType('access_key');

        $hostedZone = new HostedZone();
        $hostedZone->setAccount($account);
        $hostedZone->setAwsId('/hostedzone/Z' . uniqid());
        $hostedZone->setName('test-' . uniqid() . '.example.com.');

        return $hostedZone;
    }

    /**
     * @return ServiceEntityRepository<HostedZone>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return self::getService(HostedZoneRepository::class);
    }

    public function testInstanceCreation(): void
    {
        $repository = self::getService(HostedZoneRepository::class);

        $this->assertInstanceOf(HostedZoneRepository::class, $repository);
        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);
    }

    public function testSaveAndFindHostedZone(): void
    {
        $account = new AwsAccount();
        $account->setName('Test Account');
        $account->setCredentialsType('access_key');

        $hostedZone = new HostedZone();
        $hostedZone->setAccount($account);
        $hostedZone->setAwsId('/hostedzone/Z123456789');
        $hostedZone->setName('example.com.');

        $repository = self::getService(HostedZoneRepository::class);
        $repository->save($hostedZone, true);

        $foundZone = $repository->find($hostedZone->getId());

        $this->assertNotNull($foundZone);
        $this->assertEquals($hostedZone->getId(), $foundZone->getId());
        $this->assertEquals('example.com.', $foundZone->getName());
        $this->assertEquals('/hostedzone/Z123456789', $foundZone->getAwsId());
    }

    public function testFindOneByAccountAndAwsId(): void
    {
        $account = new AwsAccount();
        $account->setName('Test Account');
        $account->setCredentialsType('access_key');

        $hostedZone = new HostedZone();
        $hostedZone->setAccount($account);
        $hostedZone->setAwsId('/hostedzone/Z987654321');
        $hostedZone->setName('findtest.com.');

        $repository = self::getService(HostedZoneRepository::class);
        $repository->save($hostedZone, true);

        $foundZone = $repository->findOneByAccountAndAwsId($account, '/hostedzone/Z987654321');

        $this->assertNotNull($foundZone);
        $this->assertEquals($hostedZone->getId(), $foundZone->getId());
        $this->assertEquals('findtest.com.', $foundZone->getName());
    }

    public function testFindOneByAccountAndAwsIdReturnsNullForNonExistent(): void
    {
        $account = new AwsAccount();
        $account->setName('Test Account');
        $account->setCredentialsType('access_key');

        $repository = self::getService(HostedZoneRepository::class);

        $foundZone = $repository->findOneByAccountAndAwsId($account, '/hostedzone/ZNON123456');

        $this->assertNull($foundZone);
    }

    public function testFindByAccount(): void
    {
        $account1 = new AwsAccount();
        $account1->setName('Test Account 1');
        $account1->setCredentialsType('access_key');

        $account2 = new AwsAccount();
        $account2->setName('Test Account 2');
        $account2->setCredentialsType('access_key');

        $zone1 = new HostedZone();
        $zone1->setAccount($account1);
        $zone1->setAwsId('/hostedzone/Z111111111');
        $zone1->setName('account1-zone1.com.');

        $zone2 = new HostedZone();
        $zone2->setAccount($account1);
        $zone2->setAwsId('/hostedzone/Z222222222');
        $zone2->setName('account1-zone2.com.');

        $zone3 = new HostedZone();
        $zone3->setAccount($account2);
        $zone3->setAwsId('/hostedzone/Z333333333');
        $zone3->setName('account2-zone1.com.');

        $repository = self::getService(HostedZoneRepository::class);
        $repository->save($zone1);
        $repository->save($zone2);
        $repository->save($zone3, true);

        $account1Zones = $repository->findByAccount($account1);
        $account2Zones = $repository->findByAccount($account2);

        $this->assertGreaterThanOrEqual(2, count($account1Zones));
        $this->assertGreaterThanOrEqual(1, count($account2Zones));

        // 验证我们创建的托管区域在结果中
        $account1ZoneIds = array_map(fn ($z) => $z->getId(), $account1Zones);
        $account2ZoneIds = array_map(fn ($z) => $z->getId(), $account2Zones);

        $this->assertContains($zone1->getId(), $account1ZoneIds);
        $this->assertContains($zone2->getId(), $account1ZoneIds);
        $this->assertContains($zone3->getId(), $account2ZoneIds);
    }

    public function testRemoveHostedZone(): void
    {
        $account = new AwsAccount();
        $account->setName('Test Account');
        $account->setCredentialsType('access_key');

        $hostedZone = new HostedZone();
        $hostedZone->setAccount($account);
        $hostedZone->setAwsId('/hostedzone/Z444444444');
        $hostedZone->setName('removable.com.');

        $repository = self::getService(HostedZoneRepository::class);
        $repository->save($hostedZone, true);

        $zoneId = $hostedZone->getId();

        $repository->remove($hostedZone, true);

        $foundZone = $repository->find($zoneId);
        $this->assertNull($foundZone);
    }

    public function testSaveWithoutFlush(): void
    {
        $account = new AwsAccount();
        $account->setName('Test Account');
        $account->setCredentialsType('access_key');

        $hostedZone = new HostedZone();
        $hostedZone->setAccount($account);
        $hostedZone->setAwsId('/hostedzone/Z555555555');
        $hostedZone->setName('no-flush.com.');

        $repository = self::getService(HostedZoneRepository::class);
        $repository->save($hostedZone, false);

        // Explicitly flush
        self::getEntityManager()->flush();

        // Now it should be findable
        $foundZone = $repository->find($hostedZone->getId());
        $this->assertNotNull($foundZone);
    }

    public function testRemoveWithoutFlushCustom(): void
    {
        $account = new AwsAccount();
        $account->setName('Test Account');
        $account->setCredentialsType('access_key');

        $hostedZone = new HostedZone();
        $hostedZone->setAccount($account);
        $hostedZone->setAwsId('/hostedzone/Z666666666');
        $hostedZone->setName('remove-no-flush.com.');

        $repository = self::getService(HostedZoneRepository::class);
        $repository->save($hostedZone, true);

        $zoneId = $hostedZone->getId();

        $repository->remove($hostedZone, false);

        // Entity should still be findable before flush
        $foundZone = $repository->find($zoneId);
        $this->assertNotNull($foundZone);

        // Explicitly flush
        self::getEntityManager()->flush();

        // Now it should be removed
        $foundZone = $repository->find($zoneId);
        $this->assertNull($foundZone);
    }
}
