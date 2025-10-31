<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Account;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\AwsRoute53Bundle\Contracts\AccountResolverInterface;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Repository\AwsAccountRepository;

#[Autoconfigure(public: true)]
final class AccountResolver implements AccountResolverInterface
{
    public function __construct(
        private readonly AwsAccountRepository $accountRepository,
    ) {
    }

    /** @return AwsAccount[] */
    public function resolveAccounts(?string $accountFilter = null): array
    {
        return $this->accountRepository->findAccountsWithFilter($accountFilter);
    }

    public function resolveAccount(string $accountIdentifier): ?AwsAccount
    {
        return $this->accountRepository->findAccountByIdentifier($accountIdentifier);
    }

    /** @return AwsAccount[] */
    public function getEnabledAccounts(): array
    {
        return $this->accountRepository->findEnabledAccounts();
    }
}
