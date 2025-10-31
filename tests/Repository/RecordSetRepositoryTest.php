<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;
use Tourze\AwsRoute53Bundle\Entity\RecordSet;
use Tourze\AwsRoute53Bundle\Repository\RecordSetRepository;
use Tourze\AwsRoute53Bundle\Tests\AbstractUuidRepositoryTestCase;
use Tourze\AwsRoute53Bundle\Tests\Helper\FixtureLoader;

/**
 * @internal
 *
 * @template-extends AbstractUuidRepositoryTestCase<RecordSet>
 */
#[CoversClass(RecordSetRepository::class)]
#[RunTestsInSeparateProcesses]
final class RecordSetRepositoryTest extends AbstractUuidRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // 手动加载测试数据以确保有数据库记录
        FixtureLoader::loadRecordSetFixtures(self::getEntityManager());
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

        $recordSet = new RecordSet();
        $recordSet->setZone($hostedZone);
        $recordSet->setName('www.' . $hostedZone->getName());
        $recordSet->setType('A');
        $recordSet->setTtl(300);

        return $recordSet;
    }

    /**
     * @return ServiceEntityRepository<RecordSet>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return self::getService(RecordSetRepository::class);
    }

    public function testInstanceCreation(): void
    {
        $repository = self::getService(RecordSetRepository::class);

        $this->assertInstanceOf(RecordSetRepository::class, $repository);
        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);
    }

    public function testSaveAndFindRecordSet(): void
    {
        $account = new AwsAccount();
        $account->setName('Test Account');
        $account->setCredentialsType('access_key');

        $hostedZone = new HostedZone();
        $hostedZone->setAccount($account);
        $hostedZone->setAwsId('/hostedzone/Z123456789');
        $hostedZone->setName('example.com.');

        $recordSet = new RecordSet();
        $recordSet->setZone($hostedZone);
        $recordSet->setName('www.example.com.');
        $recordSet->setType('A');
        $recordSet->setTtl(300);
        $recordSet->setResourceRecords(['Value' => '192.0.2.1']);

        $repository = self::getService(RecordSetRepository::class);
        $repository->save($recordSet, true);

        $foundRecord = $repository->find($recordSet->getId());

        $this->assertNotNull($foundRecord);
        $this->assertEquals($recordSet->getId(), $foundRecord->getId());
        $this->assertEquals('www.example.com.', $foundRecord->getName());
        $this->assertEquals('A', $foundRecord->getType());
        $this->assertEquals(300, $foundRecord->getTtl());
    }

    public function testFindOneByZoneNameTypeAndSetIdentifier(): void
    {
        $account = new AwsAccount();
        $account->setName('Test Account');
        $account->setCredentialsType('access_key');

        $hostedZone = new HostedZone();
        $hostedZone->setAccount($account);
        $hostedZone->setAwsId('/hostedzone/Z987654321');
        $hostedZone->setName('findtest.com.');

        $recordSet = new RecordSet();
        $recordSet->setZone($hostedZone);
        $recordSet->setName('api.findtest.com.');
        $recordSet->setType('CNAME');
        $recordSet->setTtl(600);
        $recordSet->setSetIdentifier('primary');

        $repository = self::getService(RecordSetRepository::class);
        $repository->save($recordSet, true);

        $foundRecord = $repository->findOneByZoneNameTypeAndSetIdentifier(
            $hostedZone,
            'api.findtest.com.',
            'CNAME',
            'primary'
        );

        $this->assertNotNull($foundRecord);
        $this->assertEquals($recordSet->getId(), $foundRecord->getId());
        $this->assertEquals('api.findtest.com.', $foundRecord->getName());
        $this->assertEquals('CNAME', $foundRecord->getType());
        $this->assertEquals('primary', $foundRecord->getSetIdentifier());
    }

    public function testFindOneByZoneNameTypeAndSetIdentifierWithNullSetIdentifier(): void
    {
        $account = new AwsAccount();
        $account->setName('Test Account');
        $account->setCredentialsType('access_key');

        $hostedZone = new HostedZone();
        $hostedZone->setAccount($account);
        $hostedZone->setAwsId('/hostedzone/Z111222333');
        $hostedZone->setName('nulltest.com.');

        $recordSet = new RecordSet();
        $recordSet->setZone($hostedZone);
        $recordSet->setName('mail.nulltest.com.');
        $recordSet->setType('MX');
        $recordSet->setTtl(1800);
        $recordSet->setSetIdentifier(null);

        $repository = self::getService(RecordSetRepository::class);
        $repository->save($recordSet, true);

        $foundRecord = $repository->findOneByZoneNameTypeAndSetIdentifier(
            $hostedZone,
            'mail.nulltest.com.',
            'MX',
            null
        );

        $this->assertNotNull($foundRecord);
        $this->assertEquals($recordSet->getId(), $foundRecord->getId());
        $this->assertEquals('mail.nulltest.com.', $foundRecord->getName());
        $this->assertEquals('MX', $foundRecord->getType());
        $this->assertNull($foundRecord->getSetIdentifier());
    }

    public function testFindOneByZoneNameTypeAndSetIdentifierReturnsNullForNonExistent(): void
    {
        $account = new AwsAccount();
        $account->setName('Test Account');
        $account->setCredentialsType('access_key');

        $hostedZone = new HostedZone();
        $hostedZone->setAccount($account);
        $hostedZone->setAwsId('/hostedzone/Z444555666');
        $hostedZone->setName('norecord.com.');

        $repository = self::getService(RecordSetRepository::class);

        $foundRecord = $repository->findOneByZoneNameTypeAndSetIdentifier(
            $hostedZone,
            'nonexistent.norecord.com.',
            'A',
            null
        );

        $this->assertNull($foundRecord);
    }

    public function testFindByZone(): void
    {
        $account = new AwsAccount();
        $account->setName('Test Account');
        $account->setCredentialsType('access_key');

        $zone1 = new HostedZone();
        $zone1->setAccount($account);
        $zone1->setAwsId('/hostedzone/Z111111111');
        $zone1->setName('zone1.com.');

        $zone2 = new HostedZone();
        $zone2->setAccount($account);
        $zone2->setAwsId('/hostedzone/Z222222222');
        $zone2->setName('zone2.com.');

        $record1 = new RecordSet();
        $record1->setZone($zone1);
        $record1->setName('www.zone1.com.');
        $record1->setType('A');
        $record1->setTtl(300);

        $record2 = new RecordSet();
        $record2->setZone($zone1);
        $record2->setName('api.zone1.com.');
        $record2->setType('A');
        $record2->setTtl(300);

        $record3 = new RecordSet();
        $record3->setZone($zone2);
        $record3->setName('www.zone2.com.');
        $record3->setType('A');
        $record3->setTtl(300);

        $repository = self::getService(RecordSetRepository::class);
        $repository->save($record1);
        $repository->save($record2);
        $repository->save($record3, true);

        $zone1Records = $repository->findByZone($zone1);
        $zone2Records = $repository->findByZone($zone2);

        $this->assertGreaterThanOrEqual(2, count($zone1Records));
        $this->assertGreaterThanOrEqual(1, count($zone2Records));

        // 验证我们创建的记录在结果中
        $zone1RecordIds = array_map(fn ($r) => $r->getId(), $zone1Records);
        $zone2RecordIds = array_map(fn ($r) => $r->getId(), $zone2Records);

        $this->assertContains($record1->getId(), $zone1RecordIds);
        $this->assertContains($record2->getId(), $zone1RecordIds);
        $this->assertContains($record3->getId(), $zone2RecordIds);
    }

    public function testRemoveRecordSet(): void
    {
        $account = new AwsAccount();
        $account->setName('Test Account');
        $account->setCredentialsType('access_key');

        $hostedZone = new HostedZone();
        $hostedZone->setAccount($account);
        $hostedZone->setAwsId('/hostedzone/Z777888999');
        $hostedZone->setName('removable.com.');

        $recordSet = new RecordSet();
        $recordSet->setZone($hostedZone);
        $recordSet->setName('remove.removable.com.');
        $recordSet->setType('TXT');
        $recordSet->setTtl(60);

        $repository = self::getService(RecordSetRepository::class);
        $repository->save($recordSet, true);

        $recordId = $recordSet->getId();

        $repository->remove($recordSet, true);

        $foundRecord = $repository->find($recordId);
        $this->assertNull($foundRecord);
    }

    public function testSaveWithoutFlush(): void
    {
        $account = new AwsAccount();
        $account->setName('Test Account');
        $account->setCredentialsType('access_key');

        $hostedZone = new HostedZone();
        $hostedZone->setAccount($account);
        $hostedZone->setAwsId('/hostedzone/Z555666777');
        $hostedZone->setName('noflush.com.');

        $recordSet = new RecordSet();
        $recordSet->setZone($hostedZone);
        $recordSet->setName('test.noflush.com.');
        $recordSet->setType('A');
        $recordSet->setTtl(120);

        $repository = self::getService(RecordSetRepository::class);
        $repository->save($recordSet, false);

        // Explicitly flush
        self::getEntityManager()->flush();

        // Now it should be findable
        $foundRecord = $repository->find($recordSet->getId());
        $this->assertNotNull($foundRecord);
    }

    public function testRemoveWithoutFlushCustom(): void
    {
        $account = new AwsAccount();
        $account->setName('Test Account');
        $account->setCredentialsType('access_key');

        $hostedZone = new HostedZone();
        $hostedZone->setAccount($account);
        $hostedZone->setAwsId('/hostedzone/Z888999000');
        $hostedZone->setName('remove-no-flush.com.');

        $recordSet = new RecordSet();
        $recordSet->setZone($hostedZone);
        $recordSet->setName('test.remove-no-flush.com.');
        $recordSet->setType('AAAA');
        $recordSet->setTtl(240);

        $repository = self::getService(RecordSetRepository::class);
        $repository->save($recordSet, true);

        $recordId = $recordSet->getId();

        $repository->remove($recordSet, false);

        // Entity should still be findable before flush
        $foundRecord = $repository->find($recordId);
        $this->assertNotNull($foundRecord);

        // Explicitly flush
        self::getEntityManager()->flush();

        // Now it should be removed
        $foundRecord = $repository->find($recordId);
        $this->assertNull($foundRecord);
    }

    public function testRecordSetTypes(): void
    {
        $account = new AwsAccount();
        $account->setName('Test Account');
        $account->setCredentialsType('access_key');

        $hostedZone = new HostedZone();
        $hostedZone->setAccount($account);
        $hostedZone->setAwsId('/hostedzone/Z123456789');
        $hostedZone->setName('types.com.');

        $recordTypes = ['A', 'AAAA', 'CNAME', 'MX', 'NS', 'PTR', 'SOA', 'SRV', 'TXT', 'CAA'];
        $repository = self::getService(RecordSetRepository::class);

        foreach ($recordTypes as $type) {
            $recordSet = new RecordSet();
            $recordSet->setZone($hostedZone);
            $recordSet->setName(strtolower($type) . '.types.com.');
            $recordSet->setType($type);
            $recordSet->setTtl(300);

            $repository->save($recordSet, false);
            $this->assertEquals($type, $recordSet->getType());
        }

        self::getEntityManager()->flush();

        $zoneRecords = $repository->findByZone($hostedZone);
        $this->assertGreaterThanOrEqual(count($recordTypes), count($zoneRecords));
    }
}
