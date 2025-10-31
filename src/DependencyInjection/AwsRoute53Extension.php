<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class AwsRoute53Extension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
