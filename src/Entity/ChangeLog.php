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
#[ORM\Table(name: 'change_logs', options: ['comment' => '操作日志表'])]
#[ORM\Index(columns: ['account_id', 'created_at'], name: 'change_logs_idx_account_created')]
#[ORM\Index(columns: ['zone_id', 'created_at'], name: 'change_logs_idx_zone_created')]
class ChangeLog implements \Stringable
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true, options: ['comment' => '主键UUID'])]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: AwsAccount::class)]
    #[ORM\JoinColumn(name: 'account_id', nullable: false)]
    private AwsAccount $account;

    #[ORM\ManyToOne(targetEntity: HostedZone::class)]
    #[ORM\JoinColumn(name: 'zone_id', nullable: true)]
    private ?HostedZone $zone = null;

    #[ORM\Column(name: 'record_key', type: Types::STRING, length: 512, options: ['comment' => '记录唯一标识'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 512)]
    #[IndexColumn]
    private string $recordKey;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '操作类型'])]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['CREATE', 'DELETE', 'UPSERT'])]
    private string $action;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '变更前数据'])]
    /** @var array<string, mixed>|null */
    #[Assert\Type(type: 'array')]
    private ?array $before = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '变更后数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $after = null;

    #[ORM\Column(name: 'plan_id', type: Types::STRING, length: 36, nullable: true, options: ['comment' => '执行计划ID'])]
    #[Assert\Length(max: 36)]
    #[IndexColumn]
    private ?string $planId = null;

    #[ORM\Column(name: 'applied_at', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '应用时间'])]
    #[Assert\Type(type: 'DateTimeImmutable')]
    #[IndexColumn]
    private ?\DateTimeImmutable $appliedAt = null;

    #[ORM\Column(name: 'aws_change_id', type: Types::STRING, length: 32, nullable: true, options: ['comment' => 'AWS变更ID'])]
    #[Assert\Length(max: 32)]
    private ?string $awsChangeId = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'pending', 'comment' => '操作状态'])]
    #[Assert\Choice(choices: ['pending', 'applied', 'failed'])]
    #[IndexColumn]
    private string $status = 'pending';

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '错误信息'])]
    #[Assert\Length(max: 5000)]
    private ?string $error = null;

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

    public function getAccount(): AwsAccount
    {
        return $this->account;
    }

    public function setAccount(AwsAccount $account): void
    {
        $this->account = $account;
        $this->touch();
    }

    public function getZone(): ?HostedZone
    {
        return $this->zone;
    }

    public function setZone(?HostedZone $zone): void
    {
        $this->zone = $zone;
        $this->touch();
    }

    public function getRecordKey(): string
    {
        return $this->recordKey;
    }

    public function setRecordKey(string $recordKey): void
    {
        $this->recordKey = $recordKey;
        $this->touch();
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
        $this->touch();
    }

    /** @return array<string, mixed>|null */
    public function getBefore(): ?array
    {
        return $this->before;
    }

    /** @param array<string, mixed>|null $before */
    public function setBefore(?array $before): void
    {
        $this->before = $before;
        $this->touch();
    }

    /** @return array<string, mixed>|null */
    public function getAfter(): ?array
    {
        return $this->after;
    }

    /** @param array<string, mixed>|null $after */
    public function setAfter(?array $after): void
    {
        $this->after = $after;
        $this->touch();
    }

    public function getPlanId(): ?string
    {
        return $this->planId;
    }

    public function setPlanId(?string $planId): void
    {
        $this->planId = $planId;
        $this->touch();
    }

    public function getAppliedAt(): ?\DateTimeImmutable
    {
        return $this->appliedAt;
    }

    public function setAppliedAt(?\DateTimeImmutable $appliedAt): void
    {
        $this->appliedAt = $appliedAt;
        $this->touch();
    }

    public function getAwsChangeId(): ?string
    {
        return $this->awsChangeId;
    }

    public function setAwsChangeId(?string $awsChangeId): void
    {
        $this->awsChangeId = $awsChangeId;
        $this->touch();
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->touch();
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): void
    {
        $this->error = $error;
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
        return "{$this->recordKey} ({$this->action})";
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
