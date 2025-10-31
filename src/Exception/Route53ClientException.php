<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Exception;

final class Route53ClientException extends \RuntimeException
{
    public static function lockAcquisitionFailed(string $operation): self
    {
        return new self("Cannot acquire lock for {$operation} synchronization");
    }

    public static function syncOperationFailed(string $operation, string $reason): self
    {
        return new self("Route53 {$operation} operation failed: {$reason}");
    }
}
