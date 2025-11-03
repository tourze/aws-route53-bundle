<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AwsRoute53Bundle\Controller\Admin\ChangeLogCrudController;
use Tourze\AwsRoute53Bundle\Entity\ChangeLog;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(ChangeLogCrudController::class)]
#[RunTestsInSeparateProcesses]
class ChangeLogCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected static function getControllerFqcn(): string
    {
        return ChangeLogCrudController::class;
    }

    protected static function getEntityFqcn(): string
    {
        return ChangeLog::class;
    }

    /**
     * @return ChangeLogCrudController
     * @phpstan-return ChangeLogCrudController
     */
    protected function getControllerService(): ChangeLogCrudController
    {
        /** @var ChangeLogCrudController */
        return self::getContainer()->get(ChangeLogCrudController::class);
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield 'id' => ['ID'];
        yield 'account' => ['AWS账户'];
        yield 'zone' => ['托管区域'];
        yield 'recordKey' => ['记录唯一标识'];
        yield 'action' => ['操作类型'];
        yield 'appliedAt' => ['应用时间'];
        yield 'status' => ['操作状态'];
        yield 'createdAt' => ['创建时间'];
    }

    public static function provideNewPageFields(): iterable
    {
        yield 'account' => ['account'];
        yield 'zone' => ['zone'];
        yield 'recordKey' => ['recordKey'];
        yield 'action' => ['action'];
        yield 'planId' => ['planId'];
        yield 'status' => ['status'];
    }

    public static function provideEditPageFields(): iterable
    {
        // EDIT 操作已禁用（变更日志为只读审计数据）
        // 但为了满足测试框架要求，返回至少一个字段以避免空数据集错误
        // 实际测试会因为 Action 被禁用而自动跳过
        yield 'account' => ['account'];
    }

    public function testEntityFqcn(): void
    {
        $this->assertSame(ChangeLog::class, ChangeLogCrudController::getEntityFqcn());
    }

    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', $this->generateAdminUrl('new'));

        $form = $crawler->selectButton('Create')->form();
        $crawler = $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('should not be blank', $crawler->filter('.invalid-feedback')->text());
    }
}
