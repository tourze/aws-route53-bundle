<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Client;

use AsyncAws\Route53\Route53Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\AwsRoute53Bundle\Contracts\Route53ClientFactoryInterface;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;

#[Autoconfigure(public: true)]
final class Route53ClientFactory implements Route53ClientFactoryInterface
{
    /** @var array<string, Route53Client> */
    private array $clients = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly bool $enableCache = true,
    ) {
    }

    public function createClient(AwsAccount $account): Route53Client
    {
        $this->logger->debug('Creating Route53 client for account', [
            'account_id' => $account->getId()->toString(),
            'account_name' => $account->getName(),
        ]);

        $config = $this->createConfiguration($account);

        return new Route53Client($config);
    }

    public function getOrCreateClient(AwsAccount $account): Route53Client
    {
        if (!$this->enableCache) {
            return $this->createClient($account);
        }

        $cacheKey = $account->getId()->toString();

        if (!isset($this->clients[$cacheKey])) {
            $this->clients[$cacheKey] = $this->createClient($account);
        }

        return $this->clients[$cacheKey];
    }

    public function clearCache(?AwsAccount $account = null): void
    {
        if (null === $account) {
            $this->clients = [];
            $this->logger->debug('Cleared all Route53 client cache');

            return;
        }

        $cacheKey = $account->getId()->toString();
        unset($this->clients[$cacheKey]);

        $this->logger->debug('Cleared Route53 client cache for account', [
            'account_id' => $account->getId()->toString(),
        ]);
    }

    /** @return array{region: string, endpoint?: string} */
    private function createConfiguration(AwsAccount $account): array
    {
        $config = [
            'region' => $account->getDefaultRegion(),
        ];

        if (null !== $account->getEndpoint()) {
            $config['endpoint'] = $account->getEndpoint();
        }

        return $config;
    }
}
