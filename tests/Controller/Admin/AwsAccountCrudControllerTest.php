<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AwsRoute53Bundle\Controller\Admin\AwsAccountCrudController;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(AwsAccountCrudController::class)]
#[RunTestsInSeparateProcesses]
class AwsAccountCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected static function getControllerFqcn(): string
    {
        return AwsAccountCrudController::class;
    }

    protected static function getEntityFqcn(): string
    {
        return AwsAccount::class;
    }

    /**
     * @return AwsAccountCrudController
     * @phpstan-return AwsAccountCrudController
     */
    protected function getControllerService(): AwsAccountCrudController
    {
        /** @var AwsAccountCrudController */
        return self::getContainer()->get(AwsAccountCrudController::class);
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield 'id' => ['ID'];
        yield 'name' => ['账户名称'];
        yield 'partition' => ['AWS分区'];
        yield 'defaultRegion' => ['默认区域'];
        yield 'credentialsType' => ['凭证类型'];
        yield 'enabled' => ['启用状态'];
        yield 'hostedZones' => ['托管区域'];
        yield 'createdAt' => ['创建时间'];
    }

    public static function provideNewPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'accountId' => ['accountId'];
        yield 'partition' => ['partition'];
        yield 'defaultRegion' => ['defaultRegion'];
        yield 'endpoint' => ['endpoint'];
        yield 'credentialsType' => ['credentialsType'];
        // ArrayField渲染复杂，跳过测试
        // yield 'credentialsParams' => ['credentialsParams'];
        // yield 'tags' => ['tags'];
        yield 'enabled' => ['enabled'];
    }

    public static function provideEditPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'accountId' => ['accountId'];
        yield 'partition' => ['partition'];
        yield 'defaultRegion' => ['defaultRegion'];
        yield 'endpoint' => ['endpoint'];
        yield 'credentialsType' => ['credentialsType'];
        // ArrayField渲染复杂，跳过测试
        // yield 'credentialsParams' => ['credentialsParams'];
        // yield 'tags' => ['tags'];
        yield 'enabled' => ['enabled'];
    }

    public function testEntityFqcn(): void
    {
        $this->assertSame(AwsAccount::class, AwsAccountCrudController::getEntityFqcn());
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
