<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;

final class HostedZoneFixtures extends Fixture implements DependentFixtureInterface
{
    public const REFERENCE_HOSTED_ZONE_EXAMPLE_COM = 'hosted-zone-example-com';
    public const REFERENCE_HOSTED_ZONE_TEST_COM = 'hosted-zone-test-com';

    public function load(ObjectManager $manager): void
    {
        $productionAccount = $this->getReference(AwsAccountFixtures::REFERENCE_AWS_ACCOUNT_PRODUCTION, AwsAccount::class);
        $stagingAccount = $this->getReference(AwsAccountFixtures::REFERENCE_AWS_ACCOUNT_STAGING, AwsAccount::class);

        $exampleZone = new HostedZone();
        $exampleZone->setAccount($productionAccount);
        $exampleZone->setName('test-domain.example.');
        $exampleZone->setAwsId('Z123456789ABCDEF');
        $exampleZone->setComment('Test domain for testing purposes');
        $exampleZone->setIsPrivate(false);

        $manager->persist($exampleZone);
        $this->addReference(self::REFERENCE_HOSTED_ZONE_EXAMPLE_COM, $exampleZone);

        $testZone = new HostedZone();
        $testZone->setAccount($stagingAccount);
        $testZone->setName('staging-domain.example.');
        $testZone->setAwsId('Z987654321ABCDEF');
        $testZone->setComment('Staging test domain');
        $testZone->setIsPrivate(false);

        $manager->persist($testZone);
        $this->addReference(self::REFERENCE_HOSTED_ZONE_TEST_COM, $testZone);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AwsAccountFixtures::class,
        ];
    }
}
