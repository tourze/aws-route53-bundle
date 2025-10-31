<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;
use Tourze\AwsRoute53Bundle\Entity\RecordSet;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<RecordSet>
 */
#[AsRepository(entityClass: RecordSet::class)]
final class RecordSetRepository extends ServiceEntityRepository
{
    /** @use UuidRepositoryTrait<RecordSet> */
    use UuidRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecordSet::class);
    }

    public function findOneByZoneNameTypeAndSetIdentifier(
        HostedZone $zone,
        string $name,
        string $type,
        ?string $setIdentifier,
    ): ?RecordSet {
        return $this->findOneBy([
            'zone' => $zone,
            'name' => $name,
            'type' => $type,
            'setIdentifier' => $setIdentifier,
        ]);
    }

    /**
     * @return list<RecordSet>
     */
    public function findByZone(HostedZone $zone): array
    {
        /** @var list<RecordSet> */
        return $this->findBy(['zone' => $zone]);
    }

    public function save(RecordSet $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RecordSet $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
