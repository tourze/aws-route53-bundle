<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<HostedZone>
 */
#[AsRepository(entityClass: HostedZone::class)]
final class HostedZoneRepository extends ServiceEntityRepository
{
    /** @use UuidRepositoryTrait<HostedZone> */
    use UuidRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HostedZone::class);
    }

    public function findOneByAccountAndAwsId(AwsAccount $account, string $awsId): ?HostedZone
    {
        return $this->findOneBy(['account' => $account, 'awsId' => $awsId]);
    }

    /**
     * @return list<HostedZone>
     */
    public function findByAccount(AwsAccount $account): array
    {
        /** @var list<HostedZone> */
        return $this->findBy(['account' => $account]);
    }

    public function save(HostedZone $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(HostedZone $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
