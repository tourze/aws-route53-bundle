<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Exception;

final class Route53ConfigurationException extends \InvalidArgumentException
{
    public static function unsupportedCredentialsType(string $type): self
    {
        return new self("Unsupported credentials type: {$type}");
    }

    public static function unsupportedSynchronizationMode(string $mode): self
    {
        return new self("Unsupported synchronization mode: {$mode}");
    }

    public static function invalidConfiguration(string $parameter, string $reason): self
    {
        return new self("Invalid configuration for '{$parameter}': {$reason}");
    }
}
