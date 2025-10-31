<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Uuid;

/**
 * 提供 UUID 实体仓库的安全查找功能
 * 用于在集成测试中屏蔽非法 ID 带来的类型转换异常
 *
 * @template TEntity of object
 * @mixin ServiceEntityRepository<TEntity>
 */
trait UuidRepositoryTrait
{
    /**
     * 重写 find 方法以支持 UUID 安全查找
     */
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
    {
        if (null !== $id && $this->isUuidIdentifier() && !$this->isValidUuidValue($id)) {
            return null;
        }

        return parent::find($id, $lockMode, $lockVersion);
    }

    /**
     * 重写 findOneBy 方法以支持 UUID 安全查找
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object
    {
        /** @var array<string, mixed> $normalizedCriteria */
        $normalizedCriteria = $this->normalizeIdentifierCriteria($criteria);
        if ($this->hasInvalidUuidCriteria($normalizedCriteria)) {
            return null;
        }

        return parent::findOneBy($normalizedCriteria, $orderBy);
    }

    /**
     * 重写 findBy 方法以支持 UUID 安全查找
     *
     * @param array<string, mixed> $criteria
     * @param array<string, 'ASC'|'asc'|'DESC'|'desc'>|null $orderBy
     * @return list<TEntity>
     */
    public function findBy(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        /** @var array<string, mixed> $normalizedCriteria */
        $normalizedCriteria = $this->normalizeIdentifierCriteria($criteria);
        if ($this->hasInvalidUuidCriteria($normalizedCriteria)) {
            return [];
        }

        $identifierKey = $this->extractIdentifierKey($normalizedCriteria);
        if (
            null !== $identifierKey
            && \is_array($normalizedCriteria[$identifierKey])
            && 1 === \count($normalizedCriteria)
            && null === $orderBy
            && null === $limit
            && null === $offset
        ) {
            /** @var list<mixed> $identifiers */
            $identifiers = $normalizedCriteria[$identifierKey];

            return $this->findManyByIdentifiers($identifiers);
        }

        return parent::findBy($normalizedCriteria, $orderBy, $limit, $offset);
    }

    /**
     * 重写 count 方法以支持 UUID 安全查找
     *
     * @param array<string, mixed> $criteria
     */
    public function count(array $criteria = []): int
    {
        /** @var array<string, mixed> $normalizedCriteria */
        $normalizedCriteria = $this->normalizeIdentifierCriteria($criteria);
        if ($this->hasInvalidUuidCriteria($normalizedCriteria)) {
            return 0;
        }

        return parent::count($normalizedCriteria);
    }

    /**
     * @param array<string|int, mixed> $criteria
     */
    private function hasInvalidUuidCriteria(array $criteria): bool
    {
        if (!$this->isUuidIdentifier() || [] === $criteria) {
            return false;
        }

        $metadata = $this->getRepositoryClassMetadata();
        $fieldName = $metadata->getSingleIdentifierFieldName();
        $columnName = $metadata->getSingleIdentifierColumnName();

        foreach ($criteria as $key => $value) {
            if ($key !== $fieldName && $key !== $columnName) {
                continue;
            }

            if ($this->containsInvalidUuid($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string|int, mixed> $criteria
     */
    private function extractIdentifierKey(array $criteria): ?string
    {
        if (!$this->isUuidIdentifier() || [] === $criteria) {
            return null;
        }

        $metadata = $this->getRepositoryClassMetadata();
        $fieldName = $metadata->getSingleIdentifierFieldName();
        $columnName = $metadata->getSingleIdentifierColumnName();

        foreach (array_keys($criteria) as $key) {
            if ($key === $fieldName || $key === $columnName) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @param list<mixed> $identifiers
     * @return list<TEntity>
     */
    private function findManyByIdentifiers(array $identifiers): array
    {
        $results = [];
        $seen = [];

        foreach ($identifiers as $identifier) {
            $entity = $this->find($identifier);
            if (null === $entity) {
                continue;
            }

            $id = $this->getEntityManager()->getUnitOfWork()->getSingleIdentifierValue($entity);
            $hash = $this->convertIdToHash($id);

            if (isset($seen[$hash])) {
                continue;
            }

            $seen[$hash] = true;
            $results[] = $entity;
        }

        return $results;
    }

    /**
     * @param array<string|int, mixed> $criteria
     * @return array<string|int, mixed>
     */
    private function normalizeIdentifierCriteria(array $criteria): array
    {
        if (!$this->isUuidIdentifier() || [] === $criteria) {
            return $criteria;
        }

        $metadata = $this->getRepositoryClassMetadata();
        $fieldName = $metadata->getSingleIdentifierFieldName();
        $columnName = $metadata->getSingleIdentifierColumnName();

        foreach ($criteria as $key => $value) {
            if ($key !== $fieldName && $key !== $columnName) {
                continue;
            }

            if (!\is_array($value) || 1 !== \count($value)) {
                continue;
            }

            if (array_key_exists($fieldName, $value)) {
                $criteria[$key] = $value[$fieldName];
                continue;
            }

            if (array_key_exists($columnName, $value)) {
                $criteria[$key] = $value[$columnName];
            }
        }

        return $criteria;
    }

    private function containsInvalidUuid(mixed $value): bool
    {
        if (is_array($value)) {
            if ([] === $value) {
                return true;
            }

            foreach ($value as $item) {
                if (!$this->isValidUuidValue($item)) {
                    return true;
                }
            }

            return false;
        }

        return !$this->isValidUuidValue($value);
    }

    private function isValidUuidValue(mixed $value): bool
    {
        if ($value instanceof AbstractUid) {
            return true;
        }

        if ($value instanceof \Stringable) {
            $value = (string) $value;
        }

        if (is_string($value)) {
            return Uuid::isValid($value);
        }

        if (class_exists(UuidInterface::class) && $value instanceof UuidInterface) {
            return true;
        }

        return false;
    }

    private function isUuidIdentifier(): bool
    {
        $metadata = $this->getRepositoryClassMetadata();
        $identifierFields = $metadata->getIdentifierFieldNames();

        if (1 !== \count($identifierFields)) {
            return false;
        }

        $identifierField = $identifierFields[0];
        $fieldType = $metadata->getTypeOfField($identifierField);

        return \in_array($fieldType, [Types::GUID, 'uuid', 'uuid_binary', 'uuid_binary_ordered_time'], true);
    }

    /**
     * @return ClassMetadata<TEntity>
     */
    private function getRepositoryClassMetadata(): ClassMetadata
    {
        /** @var ClassMetadata<TEntity> $metadata */
        $metadata = $this->getEntityManager()->getClassMetadata($this->getClassName());

        return $metadata;
    }

    private function convertIdToHash(mixed $id): string
    {
        if ($id instanceof AbstractUid) {
            return $id->toRfc4122();
        }

        if (is_string($id) || is_int($id)) {
            return (string) $id;
        }

        if (is_object($id)) {
            return spl_object_hash($id);
        }

        return '';
    }
}
