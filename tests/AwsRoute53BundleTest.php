<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AwsRoute53Bundle\AwsRoute53Bundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 * @phpstan-ignore symplify.forbiddenExtendOfNonAbstractClass
 */
#[CoversClass(AwsRoute53Bundle::class)]
#[RunTestsInSeparateProcesses]
final class AwsRoute53BundleTest extends AbstractBundleTestCase
{
    public function testBundleHasDependencies(): void
    {
        $dependencies = AwsRoute53Bundle::getBundleDependencies();

        $this->assertArrayHasKey('Doctrine\Bundle\DoctrineBundle\DoctrineBundle', $dependencies);
    }
}
