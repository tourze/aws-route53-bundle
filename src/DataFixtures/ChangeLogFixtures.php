<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Entity\ChangeLog;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;

final class ChangeLogFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $productionAccount = $this->getReference(AwsAccountFixtures::REFERENCE_AWS_ACCOUNT_PRODUCTION, AwsAccount::class);
        $exampleZone = $this->getReference(HostedZoneFixtures::REFERENCE_HOSTED_ZONE_EXAMPLE_COM, HostedZone::class);

        // Successful CREATE operation
        $createLog = new ChangeLog();
        $createLog->setAccount($productionAccount);
        $createLog->setZone($exampleZone);
        $createLog->setRecordKey('api.test-domain.example A');
        $createLog->setAction('CREATE');
        $createLog->setBefore(null);
        $createLog->setAfter([
            'name' => 'api.test-domain.example.',
            'type' => 'A',
            'ttl' => 300,
            'values' => ['192.168.1.20'],
        ]);
        $createLog->setPlanId('plan-create-20240101-001');
        $createLog->setAppliedAt(new \DateTimeImmutable('2024-01-01 10:00:00'));
        $createLog->setAwsChangeId('C123456789ABCDEF001');
        $createLog->setStatus('applied');

        $manager->persist($createLog);

        // Failed DELETE operation
        $deleteLog = new ChangeLog();
        $deleteLog->setAccount($productionAccount);
        $deleteLog->setZone($exampleZone);
        $deleteLog->setRecordKey('old.test-domain.example CNAME');
        $deleteLog->setAction('DELETE');
        $deleteLog->setBefore([
            'name' => 'old.test-domain.example.',
            'type' => 'CNAME',
            'ttl' => 300,
            'values' => ['legacy.test-domain.example.'],
        ]);
        $deleteLog->setAfter(null);
        $deleteLog->setPlanId('plan-delete-20240101-002');
        $deleteLog->setAppliedAt(null);
        $deleteLog->setAwsChangeId(null);
        $deleteLog->setStatus('failed');
        $deleteLog->setError('Record not found in AWS Route53');

        $manager->persist($deleteLog);

        // Pending UPSERT operation
        $upsertLog = new ChangeLog();
        $upsertLog->setAccount($productionAccount);
        $upsertLog->setZone($exampleZone);
        $upsertLog->setRecordKey('www.test-domain.example CNAME');
        $upsertLog->setAction('UPSERT');
        $upsertLog->setBefore([
            'name' => 'www.test-domain.example.',
            'type' => 'CNAME',
            'ttl' => 300,
            'values' => ['old.test-domain.example.'],
        ]);
        $upsertLog->setAfter([
            'name' => 'www.test-domain.example.',
            'type' => 'CNAME',
            'ttl' => 300,
            'values' => ['test-domain.example.'],
        ]);
        $upsertLog->setPlanId('plan-upsert-20240101-003');
        $upsertLog->setStatus('pending');

        $manager->persist($upsertLog);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AwsAccountFixtures::class,
            HostedZoneFixtures::class,
        ];
    }
}
