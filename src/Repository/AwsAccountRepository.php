<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<AwsAccount>
 */
#[AsRepository(entityClass: AwsAccount::class)]
final class AwsAccountRepository extends ServiceEntityRepository
{
    /** @use UuidRepositoryTrait<AwsAccount> */
    use UuidRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AwsAccount::class);
    }

    /**
     * @return AwsAccount[]
     */
    public function findAccountsWithFilter(?string $accountFilter = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.name', 'ASC')
        ;

        if (null !== $accountFilter && '' !== trim($accountFilter)) {
            $filters = $this->parseFilters($accountFilter);
            $orConditions = [];

            foreach ($filters as $index => $filter) {
                [$parameterValue, $parameterType] = $this->processFilterParameter($filter);
                $orConditions[] = $this->createFilterCondition($qb, $index, $parameterValue, $parameterType);
            }

            $this->applyFilterConditions($qb, $orConditions);
        }

        /** @var AwsAccount[] */
        return $qb->getQuery()->getResult();
    }

    public function findAccountByIdentifier(string $accountIdentifier): ?AwsAccount
    {
        $qb = $this->createQueryBuilder('a');

        [$parameterValue, $parameterType] = $this->processFilterParameter($accountIdentifier);

        $qb->where($qb->expr()->orX(
            $qb->expr()->eq('a.name', ':identifier'),
            $qb->expr()->eq('a.accountId', ':identifier'),
            $qb->expr()->eq('a.id', ':identifier')
        ))
            ->setParameter('identifier', $parameterValue, $parameterType)
            ->setMaxResults(1)
        ;

        /** @var AwsAccount|null */
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return AwsAccount[]
     */
    public function findEnabledAccounts(): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('a.name', 'ASC')
        ;

        /** @var AwsAccount[] */
        return $qb->getQuery()->getResult();
    }

    public function save(AwsAccount $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AwsAccount $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return string[]
     */
    private function parseFilters(string $accountFilter): array
    {
        $filters = array_map('trim', explode(',', $accountFilter));

        return array_filter($filters, fn ($filter) => '' !== $filter);
    }

    /**
     * @return array{0: mixed, 1: string|null}
     */
    private function processFilterParameter(string $filter): array
    {
        if (false === preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $filter)) {
            return [$filter, null];
        }

        try {
            return [Uuid::fromString($filter), UuidType::NAME];
        } catch (\InvalidArgumentException $e) {
            return [$filter, null];
        }
    }

    /**
     * @param QueryBuilder $qb
     * @return Orx
     */
    private function createFilterCondition(QueryBuilder $qb, int $index, mixed $parameterValue, ?string $parameterType): Orx
    {
        $condition = $qb->expr()->orX(
            $qb->expr()->eq('a.name', ":filter{$index}"),
            $qb->expr()->eq('a.accountId', ":filter{$index}"),
            $qb->expr()->eq('a.id', ":filter{$index}")
        );

        if (null !== $parameterType) {
            $qb->setParameter("filter{$index}", $parameterValue, $parameterType);
        } else {
            $qb->setParameter("filter{$index}", $parameterValue);
        }

        return $condition;
    }

    /**
     * @param QueryBuilder $qb
     * @param list<Orx> $orConditions
     */
    private function applyFilterConditions(QueryBuilder $qb, array $orConditions): void
    {
        if (1 === count($orConditions)) {
            $qb->andWhere($orConditions[0]);
        } elseif (count($orConditions) > 1) {
            $qb->andWhere($qb->expr()->orX(...$orConditions));
        }
    }
}
