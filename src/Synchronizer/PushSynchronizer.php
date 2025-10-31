<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Synchronizer;

use AsyncAws\Route53\Route53Client;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Lock\LockFactory;
use Tourze\AwsRoute53Bundle\Contracts\PushSynchronizerInterface;
use Tourze\AwsRoute53Bundle\Contracts\Route53ClientFactoryInterface;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;
use Tourze\AwsRoute53Bundle\Entity\RecordSet;
use Tourze\AwsRoute53Bundle\Exception\Route53ClientException;
use Tourze\AwsRoute53Bundle\Repository\HostedZoneRepository;
use Tourze\AwsRoute53Bundle\Repository\RecordSetRepository;

#[Autoconfigure(public: true)]
final class PushSynchronizer implements PushSynchronizerInterface
{
    public function __construct(
        private readonly Route53ClientFactoryInterface $clientFactory,
        private readonly EntityManagerInterface $entityManager,
        private readonly HostedZoneRepository $hostedZoneRepository,
        private readonly RecordSetRepository $recordSetRepository,
        private readonly LockFactory $lockFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{changes: list<array{action: string, zone: string, record: string, dry_run: bool}>, errors: list<array{zone: string, error: string}>}
     */
    public function pushToRemote(AwsAccount $account, ?HostedZone $zone = null, bool $dryRun = false): array
    {
        $lockKey = 'route53_push_' . $account->getId()->toString() . (null !== $zone ? '_' . $zone->getAwsId() : '');
        $lock = $this->lockFactory->createLock($lockKey, 1800);

        if (!$lock->acquire()) {
            throw Route53ClientException::lockAcquisitionFailed('push');
        }

        try {
            return $this->performPush($account, $zone, $dryRun);
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array{changes: list<array{action: string, zone: string, record: string, dry_run: bool}>, errors: list<array{zone: string, error: string}>}
     */
    private function performPush(AwsAccount $account, ?HostedZone $zone = null, bool $dryRun = false): array
    {
        $result = ['changes' => [], 'errors' => []];

        try {
            $client = $this->clientFactory->getOrCreateClient($account);
        } catch (\Exception $e) {
            // 如果客户端创建失败，为所有相关 zone 记录错误
            $zones = null !== $zone ? [$zone] : $this->getAccountZones($account);
            foreach ($zones as $currentZone) {
                $result['errors'][] = [
                    'zone' => $currentZone->getName(),
                    'error' => $e->getMessage(),
                ];
                $this->logger->error('Failed to push zone changes', [
                    'zone' => $currentZone->getName(),
                    'error' => $e->getMessage(),
                ]);
            }

            return $result;
        }

        $zones = null !== $zone ? [$zone] : $this->getAccountZones($account);

        foreach ($zones as $currentZone) {
            try {
                $result = $this->pushZoneChanges($client, $currentZone, $result, $dryRun);
            } catch (\Exception $e) {
                $result['errors'][] = [
                    'zone' => $currentZone->getName(),
                    'error' => $e->getMessage(),
                ];
                $this->logger->error('Failed to push zone changes', [
                    'zone' => $currentZone->getName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /** @return HostedZone[] */
    private function getAccountZones(AwsAccount $account): array
    {
        return $this->hostedZoneRepository->findByAccount($account);
    }

    /**
     * @param array{changes: list<array{action: string, zone: string, record: string, dry_run: bool}>, errors: list<array{zone: string, error: string}>} $result
     * @return array{changes: list<array{action: string, zone: string, record: string, dry_run: bool}>, errors: list<array{zone: string, error: string}>}
     */
    private function pushZoneChanges(Route53Client $client, HostedZone $zone, array $result, bool $dryRun): array
    {
        $records = $this->recordSetRepository->findByZone($zone);

        foreach ($records as $record) {
            if ($record->isManagedBySystem()) {
                continue;
            }

            if ($this->hasLocalChanges($record)) {
                $result['changes'][] = [
                    'action' => 'upsert',
                    'zone' => $zone->getName(),
                    'record' => $record->getName() . ' ' . $record->getType(),
                    'dry_run' => $dryRun,
                ];

                if (!$dryRun) {
                    $record->setRemoteFingerprint($record->getLocalFingerprint());
                    $record->setLastSeenRemoteAt(new \DateTimeImmutable());
                }
            }
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        return $result;
    }

    private function hasLocalChanges(RecordSet $record): bool
    {
        return $record->getLocalFingerprint() !== $record->getRemoteFingerprint();
    }
}
