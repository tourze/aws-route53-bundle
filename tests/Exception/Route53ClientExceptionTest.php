<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AwsRoute53Bundle\Exception\Route53ClientException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(Route53ClientException::class)]
final class Route53ClientExceptionTest extends AbstractExceptionTestCase
{
    public function testLockAcquisitionFailed(): void
    {
        $operation = 'pull';
        $exception = Route53ClientException::lockAcquisitionFailed($operation);

        $this->assertInstanceOf(Route53ClientException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame("Cannot acquire lock for {$operation} synchronization", $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testSyncOperationFailed(): void
    {
        $operation = 'push';
        $reason = 'AWS API rate limit exceeded';
        $exception = Route53ClientException::syncOperationFailed($operation, $reason);

        $this->assertInstanceOf(Route53ClientException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame("Route53 {$operation} operation failed: {$reason}", $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
