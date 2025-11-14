# AWS Route53 Bundle

[English](README.md) | [中文](README.zh-CN.md)

Multi-account AWS Route53 DNS management bundle with bidirectional synchronization for Symfony applications.

## Features

- **Multi-account Support**: Manage multiple AWS accounts with different configurations
- **Bidirectional Synchronization**: Sync DNS records between AWS Route53 and local database
- **EasyAdmin Integration**: Full CRUD interface for managing AWS accounts, hosted zones, and DNS records
- **Doctrine ORM Integration**: Full database persistence with proper entity relationships
- **Lock-based Synchronization**: Prevent concurrent synchronization conflicts
- **Comprehensive Logging**: Detailed operation logging for debugging and monitoring
- **Data Fixtures**: Pre-configured test data for development and testing

## Requirements

- PHP 8.2 or higher
- Symfony 7.3 or higher
- Doctrine ORM
- EasyAdmin Bundle 4
- AWS account with Route53 access

## Installation

```bash
composer require tourze/aws-route53-bundle
```

## Configuration

### 1. Enable the Bundle

```php
// config/bundles.php
return [
    // ...
    Tourze\AwsRoute53Bundle\AwsRoute53Bundle::class => ['all' => true],
];
```

### 2. Configure AWS Credentials

Create AWS accounts through the EasyAdmin interface or add them to your database:

```php
// Example AWS Account configuration
$awsAccount = new AwsAccount();
$awsAccount->setName('Production AWS');
$awsAccount->setAccountId('123456789012');
$awsAccount->setPartition('aws');
$awsAccount->setDefaultRegion('us-east-1');
```

### 3. Database Schema

The bundle will automatically create the following tables:

- `aws_accounts` - AWS account configurations
- `hosted_zones` - Route53 hosted zones
- `record_sets` - DNS records
- `change_logs` - Synchronization change logs

## Usage

### Basic Synchronization

```php
use Tourze\AwsRoute53Bundle\Synchronizer\PullSynchronizer;
use Tourze\AwsRoute53Bundle\Synchronizer\PushSynchronizer;

// Pull from AWS to local database
$pullSynchronizer->synchronizeAccount($awsAccount);

// Push local changes to AWS
$pushSynchronizer->synchronizeAccount($awsAccount);
```

### Working with Entities

```php
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;
use Tourze\AwsRoute53Bundle\Entity\RecordSet;

// Create new AWS account
$account = new AwsAccount();
$account->setName('My AWS Account');
$account->setAccountId('123456789012');

// Get hosted zones
$hostedZones = $hostedZoneRepository->findBy(['account' => $account]);

// Update DNS records
$record = new RecordSet();
$record->setName('example.com');
$record->setType('A');
$record->setTtl(300);
$record->setRecords(['192.168.1.1']);
```

### Console Commands

```bash
# Pull all records from AWS
php bin/console route53:pull

# Push local changes to AWS
php bin/console route53:push

# Synchronize specific account
php bin/console route53:sync --account=123456789012
```

## Entities

### AwsAccount

Represents an AWS account with Route53 access:

- `name`: Account display name
- `accountId`: 12-digit AWS account ID
- `partition`: AWS partition (aws, aws-cn, aws-us-gov)
- `defaultRegion`: Default AWS region
- `endpoint`: Custom endpoint URL (optional)

### HostedZone

Represents a Route53 hosted zone:

- `awsId`: AWS hosted zone ID
- `name`: Domain name
- `isPrivate`: Whether it's a private hosted zone

### RecordSet

Represents a DNS record set:

- `name`: Record name
- `type`: Record type (A, AAAA, CNAME, MX, etc.)
- `ttl`: Time to live
- `records`: Record values

### ChangeLog

Tracks synchronization changes:

- `action`: Performed action (CREATE, UPDATE, DELETE)
- `entityType`: Type of entity changed
- `entityId`: ID of the changed entity
- `changeData`: JSON representation of the change

## Services

### Synchronizers

- `Tourze\AwsRoute53Bundle\Synchronizer\PullSynchronizer`: Pull data from AWS
- `Tourze\AwsRoute53Bundle\Synchronizer\PushSynchronizer`: Push data to AWS
- `Tourze\AwsRoute53Bundle\Synchronizer\Route53Synchronizer`: Main synchronization orchestrator

### Repositories

- `Tourze\AwsRoute53Bundle\Repository\AwsAccountRepository`
- `Tourze\AwsRoute53Bundle\Repository\HostedZoneRepository`
- `Tourze\AwsRoute53Bundle\Repository\RecordSetRepository`
- `Tourze\AwsRoute53Bundle\Repository\ChangeLogRepository`

### Factories

- `Tourze\AwsRoute53Bundle\Service\Route53ClientFactory`: Creates configured Route53 clients

## Testing

```bash
# Run tests
vendor/bin/phpunit

# Run PHPStan analysis
vendor/bin/phpstan analyse

# Load test fixtures
php bin/console doctrine:fixtures:load --group=aws-route53
```

## Data Fixtures

The bundle includes comprehensive data fixtures for testing:

- AWS accounts with different configurations
- Sample hosted zones
- Various types of DNS records
- Change log entries

Load fixtures using:

```bash
php bin/console doctrine:fixtures:load --group=aws-route53
```

## Security Considerations

- Store AWS credentials securely using environment variables or AWS IAM roles
- Use least-privilege IAM policies for Route53 access
- Enable audit logging for all DNS changes
- Consider using AWS VPC endpoints for enhanced security

## Performance

- Uses Symfony's lock component to prevent concurrent synchronization
- Implements efficient batch operations for large DNS zones
- Caches frequently accessed data
- Supports lazy loading for large record sets

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## License

This bundle is licensed under the MIT License. See the LICENSE file for details.

## Support

For issues and questions:
- Create an issue on GitHub
- Check the documentation
- Review existing issues

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes and version history.