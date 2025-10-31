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
#[ORM\Table(name: 'aws_accounts', options: ['comment' => 'AWS账户信息表'])]
class AwsAccount implements \Stringable
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true, options: ['comment' => '主键UUID'])]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '账户名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column(name: 'account_id', type: Types::STRING, length: 12, nullable: true, options: ['comment' => 'AWS账户ID（12位数字）'])]
    #[Assert\Regex(pattern: '/^\d{12}$/', message: 'AWS Account ID must be exactly 12 digits')]
    #[IndexColumn]
    private ?string $accountId = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'aws', 'comment' => 'AWS分区类型（aws/aws-cn/aws-us-gov）'])]
    #[Assert\Choice(choices: ['aws', 'aws-cn', 'aws-us-gov'])]
    private string $partition = 'aws';

    #[ORM\Column(name: 'default_region', type: Types::STRING, length: 50, options: ['default' => 'us-east-1', 'comment' => '默认AWS区域'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private string $defaultRegion = 'us-east-1';

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '自定义终端节点URL'])]
    #[Assert\Url]
    #[Assert\Length(max: 500)]
    private ?string $endpoint = null;

    #[ORM\Column(name: 'credentials_type', type: Types::STRING, length: 50, options: ['comment' => '凭证类型'])]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['profile', 'access_key', 'assume_role', 'web_identity', 'instance_profile'])]
    private string $credentialsType;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'credentials_params', type: Types::JSON, options: ['comment' => '凭证参数JSON配置'])]
    #[Assert\Type(type: 'array')]
    private array $credentialsParams = [];

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => 'AWS标签信息'])]
    #[Assert\Type(type: 'array')]
    private ?array $tags = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否启用账户'])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'bool')]
    #[IndexColumn]
    private bool $enabled = true;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '更新时间'])]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, HostedZone> */
    #[ORM\OneToMany(mappedBy: 'account', targetEntity: HostedZone::class, cascade: ['persist', 'remove'])]
    private Collection $hostedZones;

    public function __construct()
    {
        $this->id = Uuid::v6();
        $this->hostedZones = new ArrayCollection();
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): Uuid
    {
        return $this->id;
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

    public function getAccountId(): ?string
    {
        return $this->accountId;
    }

    public function setAccountId(?string $accountId): void
    {
        $this->accountId = $accountId;
        $this->touch();
    }

    public function getPartition(): string
    {
        return $this->partition;
    }

    public function setPartition(string $partition): void
    {
        $this->partition = $partition;
        $this->touch();
    }

    public function getDefaultRegion(): string
    {
        return $this->defaultRegion;
    }

    public function setDefaultRegion(string $defaultRegion): void
    {
        $this->defaultRegion = $defaultRegion;
        $this->touch();
    }

    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    public function setEndpoint(?string $endpoint): void
    {
        $this->endpoint = $endpoint;
        $this->touch();
    }

    public function getCredentialsType(): string
    {
        return $this->credentialsType;
    }

    public function setCredentialsType(string $credentialsType): void
    {
        $this->credentialsType = $credentialsType;
        $this->touch();
    }

    /** @return array<string, mixed> */
    public function getCredentialsParams(): array
    {
        return $this->credentialsParams;
    }

    /** @param array<string, mixed> $credentialsParams */
    public function setCredentialsParams(array $credentialsParams): void
    {
        $this->credentialsParams = $credentialsParams;
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

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
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

    /** @return Collection<int, HostedZone> */
    public function getHostedZones(): Collection
    {
        return $this->hostedZones;
    }

    public function __toString(): string
    {
        $accountInfo = (null !== $this->accountId && '' !== $this->accountId) ? " ({$this->accountId})" : '';

        return "{$this->name}{$accountInfo}";
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
