<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV6;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;

#[ORM\Entity]
#[ORM\Table(name: 'hosted_zones', options: ['comment' => 'Route53托管区域表'])]
#[ORM\UniqueConstraint(name: 'uk_account_aws_id', columns: ['account_id', 'aws_id'])]
class HostedZone implements \Stringable
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true, options: ['comment' => '主键UUID'])]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: AwsAccount::class, inversedBy: 'hostedZones', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'account_id', nullable: false)]
    private AwsAccount $account;

    #[ORM\Column(name: 'aws_id', type: Types::STRING, length: 32, options: ['comment' => 'AWS托管区域ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    #[IndexColumn]
    private string $awsId;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '域名'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[IndexColumn]
    private string $name;

    #[ORM\Column(name: 'caller_ref', type: Types::STRING, length: 255, nullable: true, options: ['comment' => '调用者引用'])]
    #[Assert\Length(max: 255)]
    private ?string $callerRef = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '托管区域描述'])]
    #[Assert\Length(max: 1000)]
    private ?string $comment = null;

    #[ORM\Column(name: 'is_private', type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否为私有托管区域'])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'bool')]
    #[IndexColumn]
    private bool $isPrivate = false;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => 'AWS标签信息'])]
    #[Assert\Type(type: 'array')]
    private ?array $tags = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'vpc_associations', type: Types::JSON, nullable: true, options: ['comment' => 'VPC关联信息'])]
    #[Assert\Type(type: 'array')]
    private ?array $vpcAssociations = null;

    #[ORM\Column(name: 'rrset_count', type: Types::INTEGER, nullable: true, options: ['comment' => '记录集数量'])]
    #[Assert\PositiveOrZero]
    private ?int $rrsetCount = null;

    #[ORM\Column(name: 'source_of_truth', type: Types::STRING, length: 20, options: ['default' => 'local', 'comment' => '数据源类型（local/remote）'])]
    #[Assert\Choice(choices: ['local', 'remote'])]
    private string $sourceOfTruth = 'local';

    #[ORM\Column(name: 'remote_fingerprint', type: Types::STRING, length: 64, nullable: true, options: ['comment' => '远程数据指纹'])]
    #[Assert\Length(max: 64)]
    private ?string $remoteFingerprint = null;

    #[ORM\Column(name: 'last_sync_at', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '上次同步时间'])]
    #[Assert\Type(type: 'DateTimeImmutable')]
    private ?\DateTimeImmutable $lastSyncAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '更新时间'])]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, RecordSet> */
    #[ORM\OneToMany(mappedBy: 'zone', targetEntity: RecordSet::class, cascade: ['persist', 'remove'])]
    private Collection $recordSets;

    public function __construct()
    {
        $this->id = Uuid::v6();
        $this->recordSets = new ArrayCollection();
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getAccount(): AwsAccount
    {
        return $this->account;
    }

    public function setAccount(AwsAccount $account): void
    {
        $this->account = $account;
        $this->touch();
    }

    public function getAwsId(): string
    {
        return $this->awsId;
    }

    public function setAwsId(string $awsId): void
    {
        $this->awsId = $awsId;
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

    public function getCallerRef(): ?string
    {
        return $this->callerRef;
    }

    public function setCallerRef(?string $callerRef): void
    {
        $this->callerRef = $callerRef;
        $this->touch();
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
        $this->touch();
    }

    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate): void
    {
        $this->isPrivate = $isPrivate;
        $this->touch();
    }

    /** @return array<string, mixed>|null */
    public function getTags(): ?array
    {
        return $this->tags;
    }

    /** @param array<string, mixed>|null $tags */
    public function setTags(?array $tags): void
    {
        $this->tags = $tags;
        $this->touch();
    }

    /** @return array<string, mixed>|null */
    public function getVpcAssociations(): ?array
    {
        return $this->vpcAssociations;
    }

    /** @param array<string, mixed>|null $vpcAssociations */
    public function setVpcAssociations(?array $vpcAssociations): void
    {
        $this->vpcAssociations = $vpcAssociations;
        $this->touch();
    }

    public function getRrsetCount(): ?int
    {
        return $this->rrsetCount;
    }

    public function setRrsetCount(?int $rrsetCount): void
    {
        $this->rrsetCount = $rrsetCount;
        $this->touch();
    }

    public function getSourceOfTruth(): string
    {
        return $this->sourceOfTruth;
    }

    public function setSourceOfTruth(string $sourceOfTruth): void
    {
        $this->sourceOfTruth = $sourceOfTruth;
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

    public function getLastSyncAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncAt;
    }

    public function setLastSyncAt(?\DateTimeImmutable $lastSyncAt): void
    {
        $this->lastSyncAt = $lastSyncAt;
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

    /** @return Collection<int, RecordSet> */
    public function getRecordSets(): Collection
    {
        return $this->recordSets;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
