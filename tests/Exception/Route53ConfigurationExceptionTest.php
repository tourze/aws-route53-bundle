<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AwsRoute53Bundle\Exception\Route53ConfigurationException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(Route53ConfigurationException::class)]
final class Route53ConfigurationExceptionTest extends AbstractExceptionTestCase
{
    public function testUnsupportedCredentialsType(): void
    {
        $type = 'unsupported_type';
        $exception = Route53ConfigurationException::unsupportedCredentialsType($type);

        $this->assertInstanceOf(Route53ConfigurationException::class, $exception);
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertSame("Unsupported credentials type: {$type}", $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testUnsupportedSynchronizationMode(): void
    {
        $mode = 'unknown_mode';
        $exception = Route53ConfigurationException::unsupportedSynchronizationMode($mode);

        $this->assertInstanceOf(Route53ConfigurationException::class, $exception);
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertSame("Unsupported synchronization mode: {$mode}", $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testInvalidConfiguration(): void
    {
        $parameter = 'access_key';
        $reason = 'cannot be empty';
        $exception = Route53ConfigurationException::invalidConfiguration($parameter, $reason);

        $this->assertInstanceOf(Route53ConfigurationException::class, $exception);
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertSame("Invalid configuration for '{$parameter}': {$reason}", $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
