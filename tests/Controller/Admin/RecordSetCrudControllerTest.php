<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AwsRoute53Bundle\Controller\Admin\RecordSetCrudController;
use Tourze\AwsRoute53Bundle\Entity\RecordSet;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(RecordSetCrudController::class)]
#[RunTestsInSeparateProcesses]
class RecordSetCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected static function getControllerFqcn(): string
    {
        return RecordSetCrudController::class;
    }

    protected static function getEntityFqcn(): string
    {
        return RecordSet::class;
    }

    /**
     * @return RecordSetCrudController
     * @phpstan-return RecordSetCrudController
     */
    protected function getControllerService(): RecordSetCrudController
    {
        /** @var RecordSetCrudController $service */
        $service = static::getService(RecordSetCrudController::class);

        return $service;
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'id' => ['ID'];
        yield 'zone' => ['托管区域'];
        yield 'name' => ['记录名称'];
        yield 'type' => ['记录类型'];
        yield 'managedBySystem' => ['系统管理'];
        yield 'protected' => ['受保护'];
        yield 'createdAt' => ['创建时间'];
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'zone' => ['zone'];
        yield 'name' => ['name'];
        yield 'type' => ['type'];
        yield 'ttl' => ['ttl'];
        // 注意：以下是 ArrayField，在空值时不渲染input元素
        // yield 'aliasTarget' => ['aliasTarget'];
        // yield 'resourceRecords' => ['resourceRecords'];
        // yield 'routingPolicy' => ['routingPolicy'];
        // yield 'geoLocation' => ['geoLocation'];
        yield 'healthCheckId' => ['healthCheckId'];
        yield 'setIdentifier' => ['setIdentifier'];
        yield 'region' => ['region'];
        yield 'multiValueAnswer' => ['multiValueAnswer'];
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'zone' => ['zone'];
        yield 'name' => ['name'];
        yield 'type' => ['type'];
        yield 'ttl' => ['ttl'];
        // 注意：以下是 ArrayField，在空值时不渲染input元素
        // yield 'aliasTarget' => ['aliasTarget'];
        // yield 'resourceRecords' => ['resourceRecords'];
        // yield 'routingPolicy' => ['routingPolicy'];
        // yield 'geoLocation' => ['geoLocation'];
        yield 'healthCheckId' => ['healthCheckId'];
        yield 'setIdentifier' => ['setIdentifier'];
        yield 'region' => ['region'];
        yield 'multiValueAnswer' => ['multiValueAnswer'];
        yield 'localFingerprint' => ['localFingerprint'];
        yield 'remoteFingerprint' => ['remoteFingerprint'];
        // 以下字段有 hideOnForm()，不在表单页面显示
        // yield 'lastLocalModifiedAt' => ['lastLocalModifiedAt'];
        // yield 'lastSeenRemoteAt' => ['lastSeenRemoteAt'];
        yield 'lastChangeInfoId' => ['lastChangeInfoId'];
        yield 'managedBySystem' => ['managedBySystem'];
        yield 'protected' => ['protected'];
    }

    public function testEntityFqcn(): void
    {
        $this->assertSame(RecordSet::class, RecordSetCrudController::getEntityFqcn());
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
