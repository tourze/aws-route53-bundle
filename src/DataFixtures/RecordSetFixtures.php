<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;
use Tourze\AwsRoute53Bundle\Entity\RecordSet;

final class RecordSetFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $exampleZone = $this->getReference(HostedZoneFixtures::REFERENCE_HOSTED_ZONE_EXAMPLE_COM, HostedZone::class);
        $testZone = $this->getReference(HostedZoneFixtures::REFERENCE_HOSTED_ZONE_TEST_COM, HostedZone::class);

        // A record for example.com
        $aRecord = new RecordSet();
        $aRecord->setZone($exampleZone);
        $aRecord->setName('test-domain.example.');
        $aRecord->setType('A');
        $aRecord->setTtl(300);
        $aRecord->setResourceRecords(['Value' => '192.168.1.10']);

        $manager->persist($aRecord);

        // CNAME record for www.example.com
        $cnameRecord = new RecordSet();
        $cnameRecord->setZone($exampleZone);
        $cnameRecord->setName('www.test-domain.example.');
        $cnameRecord->setType('CNAME');
        $cnameRecord->setTtl(300);
        $cnameRecord->setResourceRecords(['Value' => 'test-domain.example.']);

        $manager->persist($cnameRecord);

        // MX record for example.com
        $mxRecord = new RecordSet();
        $mxRecord->setZone($exampleZone);
        $mxRecord->setName('test-domain.example.');
        $mxRecord->setType('MX');
        $mxRecord->setTtl(3600);
        $mxRecord->setResourceRecords(['Value' => '10 mail.test-domain.example.']);

        $manager->persist($mxRecord);

        // A record for test.com
        $testARecord = new RecordSet();
        $testARecord->setZone($testZone);
        $testARecord->setName('staging-domain.example.');
        $testARecord->setType('A');
        $testARecord->setTtl(60);
        $testARecord->setResourceRecords(['Value' => '192.168.2.10']);

        $manager->persist($testARecord);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            HostedZoneFixtures::class,
        ];
    }
}
