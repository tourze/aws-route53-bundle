<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;

final class AwsAccountFixtures extends Fixture
{
    public const REFERENCE_AWS_ACCOUNT_PRODUCTION = 'aws-account-production';
    public const REFERENCE_AWS_ACCOUNT_STAGING = 'aws-account-staging';

    public function load(ObjectManager $manager): void
    {
        $productionAccount = new AwsAccount();
        $productionAccount->setName('production-account');
        $productionAccount->setAccountId('123456789012');
        $productionAccount->setCredentialsType('profile');
        $productionAccount->setCredentialsParams(['profile' => 'production']);
        $productionAccount->setDefaultRegion('us-east-1');
        $productionAccount->setEnabled(true);

        $manager->persist($productionAccount);
        $this->addReference(self::REFERENCE_AWS_ACCOUNT_PRODUCTION, $productionAccount);

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

        $manager->persist($stagingAccount);
        $this->addReference(self::REFERENCE_AWS_ACCOUNT_STAGING, $stagingAccount);

        $manager->flush();
    }
}
