<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Contracts;

use Tourze\AwsRoute53Bundle\Entity\AwsAccount;

interface AccountResolverInterface
{
    /** @return AwsAccount[] */
    public function resolveAccounts(?string $accountFilter = null): array;

    public function resolveAccount(string $accountIdentifier): ?AwsAccount;

    /** @return AwsAccount[] */
    public function getEnabledAccounts(): array;
}
