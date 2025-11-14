# AWS Route53 Bundle

[English](README.md) | [中文](README.zh-CN.md)

用于 Symfony 应用的多账户 AWS Route53 DNS 管理包，支持双向同步。

## 功能特性

- **多账户支持**: 管理多个具有不同配置的 AWS 账户
- **双向同步**: 在 AWS Route53 和本地数据库之间同步 DNS 记录
- **EasyAdmin 集成**: 提供完整的 CRUD 界面管理 AWS 账户、托管区域和 DNS 记录
- **Doctrine ORM 集成**: 完整的数据库持久化，包含正确的实体关系
- **基于锁的同步**: 防止并发同步冲突
- **全面日志记录**: 详细的操作日志用于调试和监控
- **数据固件**: 为开发和测试提供预配置的测试数据

## 系统要求

- PHP 8.2 或更高版本
- Symfony 7.3 或更高版本
- Doctrine ORM
- EasyAdmin Bundle 4
- 具有 Route53 访问权限的 AWS 账户

## 安装

```bash
composer require tourze/aws-route53-bundle
```

## 配置

### 1. 启用 Bundle

```php
// config/bundles.php
return [
    // ...
    Tourze\AwsRoute53Bundle\AwsRoute53Bundle::class => ['all' => true],
];
```

### 2. 配置 AWS 凭证

通过 EasyAdmin 界面创建 AWS 账户或将其添加到数据库：

```php
// 示例 AWS 账户配置
$awsAccount = new AwsAccount();
$awsAccount->setName('生产环境 AWS');
$awsAccount->setAccountId('123456789012');
$awsAccount->setPartition('aws');
$awsAccount->setDefaultRegion('us-east-1');
```

### 3. 数据库架构

Bundle 会自动创建以下数据表：

- `aws_accounts` - AWS 账户配置
- `hosted_zones` - Route53 托管区域
- `record_sets` - DNS 记录
- `change_logs` - 同步变更日志

## 使用方法

### 基本同步操作

```php
use Tourze\AwsRoute53Bundle\Synchronizer\PullSynchronizer;
use Tourze\AwsRoute53Bundle\Synchronizer\PushSynchronizer;

// 从 AWS 拉取到本地数据库
$pullSynchronizer->synchronizeAccount($awsAccount);

// 推送本地更改到 AWS
$pushSynchronizer->synchronizeAccount($awsAccount);
```

### 实体操作

```php
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;
use Tourze\AwsRoute53Bundle\Entity\RecordSet;

// 创建新的 AWS 账户
$account = new AwsAccount();
$account->setName('我的 AWS 账户');
$account->setAccountId('123456789012');

// 获取托管区域
$hostedZones = $hostedZoneRepository->findBy(['account' => $account]);

// 更新 DNS 记录
$record = new RecordSet();
$record->setName('example.com');
$record->setType('A');
$record->setTtl(300);
$record->setRecords(['192.168.1.1']);
```

### 控制台命令

```bash
# 从 AWS 拉取所有记录
php bin/console route53:pull

# 推送本地更改到 AWS
php bin/console route53:push

# 同步特定账户
php bin/console route53:sync --account=123456789012
```

## 实体说明

### AwsAccount

表示具有 Route53 访问权限的 AWS 账户：

- `name`: 账户显示名称
- `accountId`: 12位 AWS 账户 ID
- `partition`: AWS 分区（aws、aws-cn、aws-us-gov）
- `defaultRegion`: 默认 AWS 区域
- `endpoint`: 自定义终端节点 URL（可选）

### HostedZone

表示 Route53 托管区域：

- `awsId`: AWS 托管区域 ID
- `name`: 域名
- `isPrivate`: 是否为私有托管区域

### RecordSet

表示 DNS 记录集：

- `name`: 记录名称
- `type`: 记录类型（A、AAAA、CNAME、MX 等）
- `ttl`: 生存时间
- `records`: 记录值

### ChangeLog

跟踪同步更改：

- `action`: 执行的操作（CREATE、UPDATE、DELETE）
- `entityType`: 更改的实体类型
- `entityId`: 更改实体的 ID
- `changeData`: 更改的 JSON 表示

## 服务说明

### 同步器

- `Tourze\AwsRoute53Bundle\Synchronizer\PullSynchronizer`: 从 AWS 拉取数据
- `Tourze\AwsRoute53Bundle\Synchronizer\PushSynchronizer`: 推送数据到 AWS
- `Tourze\AwsRoute53Bundle\Synchronizer\Route53Synchronizer`: 主同步协调器

### 仓储库

- `Tourze\AwsRoute53Bundle\Repository\AwsAccountRepository`
- `Tourze\AwsRoute53Bundle\Repository\HostedZoneRepository`
- `Tourze\AwsRoute53Bundle\Repository\RecordSetRepository`
- `Tourze\AwsRoute53Bundle\Repository\ChangeLogRepository`

### 工厂类

- `Tourze\AwsRoute53Bundle\Service\Route53ClientFactory`: 创建已配置的 Route53 客户端

## 测试

```bash
# 运行测试
vendor/bin/phpunit

# 运行 PHPStan 分析
vendor/bin/phpstan analyse

# 加载测试固件
php bin/console doctrine:fixtures:load --group=aws-route53
```

## 数据固件

Bundle 包含全面的测试数据固件：

- 具有不同配置的 AWS 账户
- 示例托管区域
- 各种类型的 DNS 记录
- 变更日志条目

使用以下命令加载固件：

```bash
php bin/console doctrine:fixtures:load --group=aws-route53
```

## 安全考虑

- 使用环境变量或 AWS IAM 角色安全存储 AWS 凭证
- 对 Route53 访问使用最小权限 IAM 策略
- 为所有 DNS 更改启用审计日志记录
- 考虑使用 AWS VPC 终端节点以增强安全性

## 性能优化

- 使用 Symfony 的锁组件防止并发同步
- 为大型 DNS 区域实现高效的批处理操作
- 缓存频繁访问的数据
- 支持大型记录集的延迟加载

## 贡献

1. Fork 仓库
2. 创建功能分支
3. 进行更改
4. 为新功能添加测试
5. 确保所有测试通过
6. 提交 Pull Request

## 许可证

此 Bundle 采用 MIT 许可证。详情请参阅 LICENSE 文件。

## 支持

如有问题和疑问：
- 在 GitHub 上创建 issue
- 查看文档
- 查看现有 issues

## 更新日志

查看 [CHANGELOG.md](CHANGELOG.md) 了解变更列表和版本历史。