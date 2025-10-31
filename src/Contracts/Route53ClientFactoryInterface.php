<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Contracts;

use AsyncAws\Route53\Route53Client;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;

interface Route53ClientFactoryInterface
{
    public function createClient(AwsAccount $account): Route53Client;

    public function getOrCreateClient(AwsAccount $account): Route53Client;

    public function clearCache(?AwsAccount $account = null): void;
}
