<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV6;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;

#[ORM\Entity]
#[ORM\Table(name: 'record_sets', options: ['comment' => 'DNS记录集表'])]
#[ORM\UniqueConstraint(name: 'uk_zone_name_type_identifier', columns: ['zone_id', 'name', 'type', 'set_identifier'])]
class RecordSet implements \Stringable
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true, options: ['comment' => '主键UUID'])]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: HostedZone::class, inversedBy: 'recordSets', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'zone_id', nullable: false)]
    private HostedZone $zone;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '记录名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[IndexColumn]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 10, options: ['comment' => '记录类型'])]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['A', 'AAAA', 'CNAME', 'MX', 'NS', 'PTR', 'SOA', 'SRV', 'TXT', 'CAA'])]
    #[IndexColumn]
    private string $type;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => 'DNS记录TTL值'])]
    #[Assert\PositiveOrZero]
    private ?int $ttl = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'alias_target', type: Types::JSON, nullable: true, options: ['comment' => '别名目标配置'])]
    #[Assert\Type(type: 'array')]
    private ?array $aliasTarget = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'resource_records', type: Types::JSON, nullable: true, options: ['comment' => '资源记录值'])]
    #[Assert\Type(type: 'array')]
    private ?array $resourceRecords = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'routing_policy', type: Types::JSON, nullable: true, options: ['comment' => '路由策略配置'])]
    #[Assert\Type(type: 'array')]
    private ?array $routingPolicy = null;

    #[ORM\Column(name: 'health_check_id', type: Types::STRING, length: 36, nullable: true, options: ['comment' => '健康检查ID'])]
    #[Assert\Length(max: 36)]
    private ?string $healthCheckId = null;

    #[ORM\Column(name: 'set_identifier', type: Types::STRING, length: 128, nullable: true, options: ['comment' => '记录集标识符'])]
    #[Assert\Length(max: 128)]
    private ?string $setIdentifier = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => 'AWS区域'])]
    #[Assert\Length(max: 50)]
    private ?string $region = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'geo_location', type: Types::JSON, nullable: true, options: ['comment' => '地理位置配置'])]
    #[Assert\Type(type: 'array')]
    private ?array $geoLocation = null;

    #[ORM\Column(name: 'multi_value_answer', type: Types::BOOLEAN, nullable: true, options: ['comment' => '是否多值答案'])]
    #[Assert\Type(type: 'bool')]
    private ?bool $multiValueAnswer = null;

    #[ORM\Column(name: 'local_fingerprint', type: Types::STRING, length: 64, nullable: true, options: ['comment' => '本地数据指纹'])]
    #[Assert\Length(max: 64)]
    private ?string $localFingerprint = null;

    #[ORM\Column(name: 'remote_fingerprint', type: Types::STRING, length: 64, nullable: true, options: ['comment' => '远程数据指纹'])]
    #[Assert\Length(max: 64)]
    private ?string $remoteFingerprint = null;

    #[ORM\Column(name: 'last_local_modified_at', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '本地最后修改时间'])]
    #[Assert\Type(type: 'DateTimeImmutable')]
    private ?\DateTimeImmutable $lastLocalModifiedAt = null;

    #[ORM\Column(name: 'last_seen_remote_at', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '远程最后见时间'])]
    #[Assert\Type(type: 'DateTimeImmutable')]
    private ?\DateTimeImmutable $lastSeenRemoteAt = null;

    #[ORM\Column(name: 'last_change_info_id', type: Types::STRING, length: 32, nullable: true, options: ['comment' => '最后变更信息ID'])]
    #[Assert\Length(max: 32)]
    private ?string $lastChangeInfoId = null;

    #[ORM\Column(name: 'managed_by_system', type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否由系统管理'])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'bool')]
    #[IndexColumn]
    private bool $managedBySystem = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否受保护'])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'bool')]
    #[IndexColumn]
    private bool $protected = false;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '更新时间'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v6();
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getZone(): HostedZone
    {
        return $this->zone;
    }

    public function setZone(HostedZone $zone): void
    {
        $this->zone = $zone;
        $this->touch();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->touch();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
        $this->touch();
    }

    public function getTtl(): ?int
    {
        return $this->ttl;
    }

    public function setTtl(?int $ttl): void
    {
        $this->ttl = $ttl;
        $this->touch();
    }

    /** @return array<string, mixed>|null */
    public function getAliasTarget(): ?array
    {
        return $this->aliasTarget;
    }

    /** @param array<string, mixed>|null $aliasTarget */
    public function setAliasTarget(?array $aliasTarget): void
    {
        $this->aliasTarget = $aliasTarget;
        $this->touch();
    }

    /** @return array<string, mixed>|null */
    public function getResourceRecords(): ?array
    {
        return $this->resourceRecords;
    }

    /** @param array<string, mixed>|null $resourceRecords */
    public function setResourceRecords(?array $resourceRecords): void
    {
        $this->resourceRecords = $resourceRecords;
        $this->touch();
    }

    /** @return array<string, mixed>|null */
    public function getRoutingPolicy(): ?array
    {
        return $this->routingPolicy;
    }

    /** @param array<string, mixed>|null $routingPolicy */
    public function setRoutingPolicy(?array $routingPolicy): void
    {
        $this->routingPolicy = $routingPolicy;
        $this->touch();
    }

    public function getHealthCheckId(): ?string
    {
        return $this->healthCheckId;
    }

    public function setHealthCheckId(?string $healthCheckId): void
    {
        $this->healthCheckId = $healthCheckId;
        $this->touch();
    }

    public function getSetIdentifier(): ?string
    {
        return $this->setIdentifier;
    }

    public function setSetIdentifier(?string $setIdentifier): void
    {
        $this->setIdentifier = $setIdentifier;
        $this->touch();
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): void
    {
        $this->region = $region;
        $this->touch();
    }

    /** @return array<string, mixed>|null */
    public function getGeoLocation(): ?array
    {
        return $this->geoLocation;
    }

    /** @param array<string, mixed>|null $geoLocation */
    public function setGeoLocation(?array $geoLocation): void
    {
        $this->geoLocation = $geoLocation;
        $this->touch();
    }

    public function isMultiValueAnswer(): ?bool
    {
        return $this->multiValueAnswer;
    }

    public function setMultiValueAnswer(?bool $multiValueAnswer): void
    {
        $this->multiValueAnswer = $multiValueAnswer;
        $this->touch();
    }

    public function getLocalFingerprint(): ?string
    {
        return $this->localFingerprint;
    }

    public function setLocalFingerprint(?string $localFingerprint): void
    {
        $this->localFingerprint = $localFingerprint;
        $this->touch();
    }

    public function getRemoteFingerprint(): ?string
    {
        return $this->remoteFingerprint;
    }

    public function setRemoteFingerprint(?string $remoteFingerprint): void
    {
        $this->remoteFingerprint = $remoteFingerprint;
        $this->touch();
    }

    public function getLastLocalModifiedAt(): ?\DateTimeImmutable
    {
        return $this->lastLocalModifiedAt;
    }

    public function setLastLocalModifiedAt(?\DateTimeImmutable $lastLocalModifiedAt): void
    {
        $this->lastLocalModifiedAt = $lastLocalModifiedAt;
        $this->touch();
    }

    public function getLastSeenRemoteAt(): ?\DateTimeImmutable
    {
        return $this->lastSeenRemoteAt;
    }

    public function setLastSeenRemoteAt(?\DateTimeImmutable $lastSeenRemoteAt): void
    {
        $this->lastSeenRemoteAt = $lastSeenRemoteAt;
        $this->touch();
    }

    public function getLastChangeInfoId(): ?string
    {
        return $this->lastChangeInfoId;
    }

    public function setLastChangeInfoId(?string $lastChangeInfoId): void
    {
        $this->lastChangeInfoId = $lastChangeInfoId;
        $this->touch();
    }

    public function isManagedBySystem(): bool
    {
        return $this->managedBySystem;
    }

    public function setManagedBySystem(bool $managedBySystem): void
    {
        $this->managedBySystem = $managedBySystem;
        $this->touch();
    }

    public function isProtected(): bool
    {
        return $this->protected;
    }

    public function setProtected(bool $protected): void
    {
        $this->protected = $protected;
        $this->touch();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function __toString(): string
    {
        return "{$this->name} ({$this->type})";
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->lastLocalModifiedAt = new \DateTimeImmutable();
    }
}
