<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AwsRoute53Bundle\Controller\Admin\HostedZoneCrudController;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(HostedZoneCrudController::class)]
#[RunTestsInSeparateProcesses]
class HostedZoneCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected static function getControllerFqcn(): string
    {
        return HostedZoneCrudController::class;
    }

    protected static function getEntityFqcn(): string
    {
        return HostedZone::class;
    }

    /**
     * @return HostedZoneCrudController
     * @phpstan-return HostedZoneCrudController
     */
    protected function getControllerService(): HostedZoneCrudController
    {
        /** @var HostedZoneCrudController $service */
        $service = static::getService(HostedZoneCrudController::class);

        return $service;
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'id' => ['ID'];
        yield 'account' => ['AWS账户'];
        yield 'awsId' => ['AWS托管区域ID'];
        yield 'name' => ['域名'];
        yield 'isPrivate' => ['私有区域'];
        yield 'rrsetCount' => ['记录集数量'];
        yield 'sourceOfTruth' => ['数据源类型'];
        yield 'recordSets' => ['记录集'];
        yield 'createdAt' => ['创建时间'];
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'account' => ['account'];
        yield 'awsId' => ['awsId'];
        yield 'name' => ['name'];
        yield 'callerRef' => ['callerRef'];
        yield 'comment' => ['comment'];
        yield 'isPrivate' => ['isPrivate'];
        // 注意：tags 和 vpcAssociations 是 ArrayField，在空值时不渲染input元素，仅显示容器和新增按钮
        // yield 'tags' => ['tags'];
        // yield 'vpcAssociations' => ['vpcAssociations'];
        yield 'sourceOfTruth' => ['sourceOfTruth'];
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'account' => ['account'];
        yield 'awsId' => ['awsId'];
        yield 'name' => ['name'];
        yield 'callerRef' => ['callerRef'];
        yield 'comment' => ['comment'];
        yield 'isPrivate' => ['isPrivate'];
        // 注意：tags 和 vpcAssociations 是 ArrayField，在空值时不渲染input元素
        // yield 'tags' => ['tags'];
        // yield 'vpcAssociations' => ['vpcAssociations'];
        // rrsetCount 字段有 hideOnForm()，不在表单页面显示
        // yield 'rrsetCount' => ['rrsetCount'];
        yield 'sourceOfTruth' => ['sourceOfTruth'];
        yield 'remoteFingerprint' => ['remoteFingerprint'];
        // lastSyncAt 字段有 hideOnForm()，不在表单页面显示
        // yield 'lastSyncAt' => ['lastSyncAt'];
    }

    public function testEntityFqcn(): void
    {
        $this->assertSame(HostedZone::class, HostedZoneCrudController::getEntityFqcn());
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
