<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AwsRoute53Bundle\DependencyInjection\AwsRoute53Extension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(AwsRoute53Extension::class)]
final class AwsRoute53ExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
}
