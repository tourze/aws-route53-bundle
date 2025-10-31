<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Synchronizer;

use AsyncAws\Route53\Enum\RRType;
use AsyncAws\Route53\Route53Client;
use AsyncAws\Route53\ValueObject\HostedZone as RemoteHostedZone;
use AsyncAws\Route53\ValueObject\ResourceRecordSet;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Lock\LockFactory;
use Tourze\AwsRoute53Bundle\Contracts\PullSynchronizerInterface;
use Tourze\AwsRoute53Bundle\Contracts\Route53ClientFactoryInterface;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;
use Tourze\AwsRoute53Bundle\Entity\RecordSet;
use Tourze\AwsRoute53Bundle\Exception\Route53ClientException;
use Tourze\AwsRoute53Bundle\Repository\HostedZoneRepository;
use Tourze\AwsRoute53Bundle\Repository\RecordSetRepository;

#[Autoconfigure(public: true)]
final class PullSynchronizer implements PullSynchronizerInterface
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
     * @return array{zones: int, records: int, changes: list<array{action: string, zone: string, record: string}>}
     */
    public function pullFromRemote(AwsAccount $account, ?HostedZone $zone = null, bool $dryRun = false): array
    {
        $lockKey = 'route53_pull_' . $account->getId()->toString() . (null !== $zone ? '_' . $zone->getAwsId() : '');
        $lock = $this->lockFactory->createLock($lockKey, 1800);

        if (!$lock->acquire()) {
            throw Route53ClientException::lockAcquisitionFailed('pull');
        }

        try {
            return $this->performPull($account, $zone, $dryRun);
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array{zones: int, records: int, changes: list<array{action: string, zone: string, record: string}>}
     */
    private function performPull(AwsAccount $account, ?HostedZone $zone = null, bool $dryRun = false): array
    {
        $client = $this->clientFactory->getOrCreateClient($account);
        $result = ['zones' => 0, 'records' => 0, 'changes' => []];

        if (null !== $zone) {
            $result = $this->pullZone($client, $account, $zone, $result, $dryRun);
        } else {
            $result = $this->pullAllZones($client, $account, $result, $dryRun);
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        return $result;
    }

    /**
     * @param array{zones: int, records: int, changes: list<array{action: string, zone: string, record: string}>} $result
     * @return array{zones: int, records: int, changes: list<array{action: string, zone: string, record: string}>}
     */
    private function pullAllZones(Route53Client $client, AwsAccount $account, array $result, bool $dryRun): array
    {
        $response = $client->listHostedZones();

        foreach ($response->getHostedZones() as $remoteZone) {
            $localZone = $this->syncHostedZone($account, $remoteZone, $dryRun);
            if (null !== $localZone) {
                ++$result['zones'];
                $result = $this->pullZoneRecords($client, $localZone, $result, $dryRun);
            }
        }

        return $result;
    }

    /**
     * @param array{zones: int, records: int, changes: list<array{action: string, zone: string, record: string}>} $result
     * @return array{zones: int, records: int, changes: list<array{action: string, zone: string, record: string}>}
     */
    private function pullZone(Route53Client $client, AwsAccount $account, HostedZone $zone, array $result, bool $dryRun): array
    {
        try {
            // 使用 listHostedZones 来查找特定的 zone，因为 getHostedZone 方法不存在
            $response = $client->listHostedZones();
            $remoteZone = null;

            foreach ($response->getHostedZones() as $hostedZone) {
                $hostedZoneId = str_replace('/hostedzone/', '', $hostedZone->getId());
                if ($hostedZoneId === $zone->getAwsId()) {
                    $remoteZone = $hostedZone;
                    break;
                }
            }

            if (null !== $remoteZone) {
                $this->syncHostedZone($account, $remoteZone, $dryRun);
                ++$result['zones'];
                $result = $this->pullZoneRecords($client, $zone, $result, $dryRun);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to pull zone', [
                'zone_id' => $zone->getAwsId(),
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    private function syncHostedZone(AwsAccount $account, RemoteHostedZone $remoteZone, bool $dryRun): ?HostedZone
    {
        $awsId = $remoteZone->getId();

        if ('' === $awsId) {
            return null;
        }

        $awsId = str_replace('/hostedzone/', '', $awsId);

        $localZone = $this->hostedZoneRepository->findOneByAccountAndAwsId($account, $awsId);

        if (null === $localZone) {
            $localZone = new HostedZone();
            $localZone->setAccount($account);
            $localZone->setAwsId($awsId);

            if (!$dryRun) {
                $this->entityManager->persist($localZone);
            }
        }

        $localZone->setName('' !== $remoteZone->getName() ? $remoteZone->getName() : '');
        $localZone->setCallerRef($remoteZone->getCallerReference());
        $localZone->setComment($remoteZone->getConfig()?->getComment());
        $localZone->setIsPrivate($remoteZone->getConfig()?->getPrivateZone() ?? false);
        $localZone->setRrsetCount($remoteZone->getResourceRecordSetCount());
        $localZone->setLastSyncAt(new \DateTimeImmutable());

        return $localZone;
    }

    /**
     * @param array{zones: int, records: int, changes: list<array{action: string, zone: string, record: string}>} $result
     * @return array{zones: int, records: int, changes: list<array{action: string, zone: string, record: string}>}
     */
    private function pullZoneRecords(Route53Client $client, HostedZone $zone, array $result, bool $dryRun): array
    {
        try {
            $response = $client->listResourceRecordSets(['HostedZoneId' => $zone->getAwsId()]);

            foreach ($response->getResourceRecordSets() as $remoteRecord) {
                $localRecord = $this->syncRecordSet($zone, $remoteRecord, $dryRun);
                if (null !== $localRecord) {
                    ++$result['records'];
                    $result['changes'][] = [
                        'action' => 'sync',
                        'zone' => $zone->getName(),
                        'record' => $remoteRecord->getName() . ' ' . $remoteRecord->getType(),
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to pull records for zone', [
                'zone_id' => $zone->getAwsId(),
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    private function syncRecordSet(HostedZone $zone, ResourceRecordSet $remoteRecord, bool $dryRun): ?RecordSet
    {
        $name = $remoteRecord->getName();
        $type = $remoteRecord->getType();
        $setIdentifier = $remoteRecord->getSetIdentifier();

        if ('' === $name) {
            return null;
        }

        $localRecord = $this->recordSetRepository->findOneByZoneNameTypeAndSetIdentifier($zone, $name, $type, $setIdentifier);

        if (null === $localRecord) {
            $localRecord = new RecordSet();
            $localRecord->setZone($zone);
            $localRecord->setName($name);
            $localRecord->setType($type);
            $localRecord->setSetIdentifier($setIdentifier);

            if (!$dryRun) {
                $this->entityManager->persist($localRecord);
            }
        }

        $localRecord->setTtl($remoteRecord->getTtl());

        if (null !== $remoteRecord->getAliasTarget()) {
            $localRecord->setAliasTarget([
                'DNSName' => $remoteRecord->getAliasTarget()->getDnsName(),
                'EvaluateTargetHealth' => $remoteRecord->getAliasTarget()->getEvaluateTargetHealth(),
                'HostedZoneId' => $remoteRecord->getAliasTarget()->getHostedZoneId(),
            ]);
        }

        $resourceRecords = $remoteRecord->getResourceRecords();
        if (count($resourceRecords) > 0) {
            $records = [];
            $index = 0;
            foreach ($resourceRecords as $rr) {
                $records['record_' . $index] = $rr->getValue();
                ++$index;
            }
            $localRecord->setResourceRecords($records);
        }

        $localRecord->setHealthCheckId($remoteRecord->getHealthCheckId());
        $localRecord->setRegion($remoteRecord->getRegion());
        $localRecord->setMultiValueAnswer($remoteRecord->getMultiValueAnswer());

        if (null !== $remoteRecord->getGeoLocation()) {
            $localRecord->setGeoLocation([
                'ContinentCode' => $remoteRecord->getGeoLocation()->getContinentCode(),
                'CountryCode' => $remoteRecord->getGeoLocation()->getCountryCode(),
                'SubdivisionCode' => $remoteRecord->getGeoLocation()->getSubdivisionCode(),
            ]);
        }

        $localRecord->setLastSeenRemoteAt(new \DateTimeImmutable());

        if (RRType::SOA === $type || RRType::NS === $type) {
            $localRecord->setManagedBySystem(true);
        }

        return $localRecord;
    }
}
