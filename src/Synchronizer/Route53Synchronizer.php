<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Synchronizer;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Lock\LockFactory;
use Tourze\AwsRoute53Bundle\Contracts\PullSynchronizerInterface;
use Tourze\AwsRoute53Bundle\Contracts\PushSynchronizerInterface;
use Tourze\AwsRoute53Bundle\Contracts\SynchronizerInterface;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;
use Tourze\AwsRoute53Bundle\Exception\Route53ClientException;
use Tourze\AwsRoute53Bundle\Exception\Route53ConfigurationException;

#[Autoconfigure(public: true)]
final class Route53Synchronizer implements SynchronizerInterface
{
    public function __construct(
        private readonly PullSynchronizerInterface $pullSynchronizer,
        private readonly PushSynchronizerInterface $pushSynchronizer,
        private readonly LockFactory $lockFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @return array<string, mixed> */
    public function pullFromRemote(AwsAccount $account, ?HostedZone $zone = null, bool $dryRun = false): array
    {
        $this->logger->info('Starting pull synchronization', [
            'account' => $account->getName(),
            'zone' => $zone?->getName(),
            'dry_run' => $dryRun,
        ]);

        $result = $this->pullSynchronizer->pullFromRemote($account, $zone, $dryRun);

        $this->logger->info('Pull synchronization completed', [
            'account' => $account->getName(),
            'zones_synced' => $result['zones'] ?? 0,
            'records_synced' => $result['records'] ?? 0,
            'dry_run' => $dryRun,
        ]);

        return $result;
    }

    /** @return array<string, mixed> */
    public function pushToRemote(AwsAccount $account, ?HostedZone $zone = null, bool $dryRun = false): array
    {
        $this->logger->info('Starting push synchronization', [
            'account' => $account->getName(),
            'zone' => $zone?->getName(),
            'dry_run' => $dryRun,
        ]);

        $result = $this->pushSynchronizer->pushToRemote($account, $zone, $dryRun);

        /** @var array<string, mixed> $changes */
        $changes = $result['changes'] ?? [];
        $this->logger->info('Push synchronization completed', [
            'account' => $account->getName(),
            'changes_applied' => count($changes),
            'dry_run' => $dryRun,
        ]);

        return $result;
    }

    /** @return array<string, mixed> */
    public function bidirectionalSync(AwsAccount $account, ?HostedZone $zone = null, string $mode = 'local_wins', bool $dryRun = false): array
    {
        $lockKey = 'route53_bidirectional_' . $account->getId()->toString() . (null !== $zone ? '_' . $zone->getAwsId() : '');
        $lock = $this->lockFactory->createLock($lockKey, 3600);

        if (!$lock->acquire()) {
            throw Route53ClientException::lockAcquisitionFailed('bidirectional');
        }

        try {
            return $this->performBidirectionalSync($account, $zone, $mode, $dryRun);
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function performBidirectionalSync(AwsAccount $account, ?HostedZone $zone = null, string $mode = 'local_wins', bool $dryRun = false): array
    {
        $this->logger->info('Starting bidirectional synchronization', [
            'account' => $account->getName(),
            'zone' => $zone?->getName(),
            'mode' => $mode,
            'dry_run' => $dryRun,
        ]);

        $result = [
            'pull' => [],
            'push' => [],
            'conflicts' => [],
            'resolved' => [],
        ];

        switch ($mode) {
            case 'local_wins':
                $result['pull'] = $this->pullFromRemote($account, $zone, true);
                $result['push'] = $this->pushToRemote($account, $zone, $dryRun);
                break;

            case 'remote_wins':
                $result['push'] = $this->pushToRemote($account, $zone, true);
                $result['pull'] = $this->pullFromRemote($account, $zone, $dryRun);
                break;

            case 'merge':
                $result['pull'] = $this->pullFromRemote($account, $zone, true);
                $result['push'] = $this->pushToRemote($account, $zone, true);
                $result = $this->resolveMergeConflicts($account, $zone, $result, $dryRun);
                break;

            default:
                throw Route53ConfigurationException::unsupportedSynchronizationMode($mode);
        }

        /** @var array<string, mixed> $pullData */
        $pullData = $result['pull'] ?? [];
        /** @var array<string, mixed> $pullChanges */
        $pullChanges = $pullData['changes'] ?? [];

        /** @var array<string, mixed> $pushData */
        $pushData = $result['push'] ?? [];
        /** @var array<string, mixed> $pushChanges */
        $pushChanges = $pushData['changes'] ?? [];

        /** @var array<string, mixed> $conflicts */
        $conflicts = $result['conflicts'] ?? [];

        $this->logger->info('Bidirectional synchronization completed', [
            'account' => $account->getName(),
            'mode' => $mode,
            'pull_changes' => count($pullChanges),
            'push_changes' => count($pushChanges),
            'conflicts' => count($conflicts),
            'dry_run' => $dryRun,
        ]);

        return $result;
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function resolveMergeConflicts(AwsAccount $account, ?HostedZone $zone, array $result, bool $dryRun): array
    {
        return $result;
    }
}
