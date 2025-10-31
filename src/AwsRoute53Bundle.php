<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;

class AwsRoute53Bundle extends Bundle implements BundleDependencyInterface
{
    /** @return array<class-string<Bundle>, array<string, bool>> */
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
            DoctrineFixturesBundle::class => ['dev' => true, 'test' => true],
        ];
    }
}
