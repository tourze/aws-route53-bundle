<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * UUID实体专用的仓库测试基类
 *
 * 此基类专门为使用UUID作为主键的实体设计。
 * 继承 AbstractRepositoryTestCase 以符合 PHPat 架构规则。
 *
 * 注意：基类中使用负数ID的测试方法会因为UUID类型转换而抛出异常，
 * 这在功能上是正确的行为（UUID实体不应接受负数ID）。
 *
 * @template TEntity of object
 * @template-extends AbstractRepositoryTestCase<TEntity>
 */
#[Medium]
#[RunTestsInSeparateProcesses]
#[CoversClass(AbstractRepositoryTestCase::class)]
abstract class AbstractUuidRepositoryTestCase extends AbstractRepositoryTestCase
{
    // 本类不需要额外的方法，直接继承基类的所有测试即可
    // 基类的测试方法是 final 的，无法被覆盖
}
