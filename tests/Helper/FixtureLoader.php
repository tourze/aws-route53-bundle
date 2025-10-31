<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\AwsRoute53Bundle\DataFixtures\AwsAccountFixtures;
use Tourze\AwsRoute53Bundle\DataFixtures\HostedZoneFixtures;
use Tourze\AwsRoute53Bundle\DataFixtures\RecordSetFixtures;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;
use Tourze\AwsRoute53Bundle\Entity\RecordSet;

/**
 * DataFixtures加载辅助类
 */
class FixtureLoader
{
    public static function loadAwsAccountFixtures(EntityManagerInterface $entityManager): void
    {
        // 清理现有数据
        $entityManager->createQuery('DELETE FROM ' . AwsAccount::class)->execute();

        // 创建测试数据
        $productionAccount = new AwsAccount();
        $productionAccount->setName('production-account');
        $productionAccount->setAccountId('123456789012');
        $productionAccount->setCredentialsType('profile');
        $productionAccount->setCredentialsParams(['profile' => 'production']);
        $productionAccount->setDefaultRegion('us-east-1');
        $productionAccount->setEnabled(true);

        $stagingAccount = new AwsAccount();
        $stagingAccount->setName('staging-account');
        $stagingAccount->setAccountId('123456789013');
        $stagingAccount->setCredentialsType('access_key');
        $stagingAccount->setCredentialsParams([
            'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
        ]);
        $stagingAccount->setDefaultRegion('us-west-2');
        $stagingAccount->setEnabled(true);

        $entityManager->persist($productionAccount);
        $entityManager->persist($stagingAccount);
        $entityManager->flush();
    }

    public static function loadHostedZoneFixtures(EntityManagerInterface $entityManager): void
    {
        // 清理现有数据
        $entityManager->createQuery('DELETE FROM ' . HostedZone::class)->execute();

        // 首先确保有账户数据
        $accounts = $entityManager->getRepository(AwsAccount::class)->findAll();
        if (0 === count($accounts)) {
            self::loadAwsAccountFixtures($entityManager);
            $accounts = $entityManager->getRepository(AwsAccount::class)->findAll();
        }

        $account = $accounts[0];

        // 创建HostedZone测试数据
        $hostedZone = new HostedZone();
        $hostedZone->setAccount($account);
        $hostedZone->setAwsId('/hostedzone/Z1234567890');
        $hostedZone->setName('example.com.');
        $hostedZone->setCallerRef('test-ref-123');
        $hostedZone->setComment('Test hosted zone');
        $hostedZone->setIsPrivate(false);
        $hostedZone->setRrsetCount(2);
        $hostedZone->setLastSyncAt(new \DateTimeImmutable());

        $entityManager->persist($hostedZone);
        $entityManager->flush();
    }

    public static function loadRecordSetFixtures(EntityManagerInterface $entityManager): void
    {
        // 清理现有数据
        $entityManager->createQuery('DELETE FROM ' . RecordSet::class)->execute();

        // 首先确保有HostedZone数据
        $hostedZones = $entityManager->getRepository(HostedZone::class)->findAll();
        if (0 === count($hostedZones)) {
            self::loadHostedZoneFixtures($entityManager);
            $hostedZones = $entityManager->getRepository(HostedZone::class)->findAll();
        }

        $hostedZone = $hostedZones[0];

        // 创建RecordSet测试数据
        $recordSet = new RecordSet();
        $recordSet->setZone($hostedZone);
        $recordSet->setName('www.example.com.');
        $recordSet->setType('A');
        $recordSet->setTtl(300);
        $recordSet->setResourceRecords(['record_0' => '192.0.2.1', 'record_1' => '192.0.2.2']);
        $recordSet->setRoutingPolicy(['type' => 'simple']);
        $recordSet->setManagedBySystem(false);
        $recordSet->setProtected(false);

        $entityManager->persist($recordSet);
        $entityManager->flush();
    }
}
