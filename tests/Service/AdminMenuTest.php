<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Tests\Service;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AwsRoute53Bundle\Service\AdminMenu;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * AdminMenu服务测试
 *
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        // Setup for AdminMenu tests
    }

    public function testGetMenuItems(): void
    {
        $adminMenu = self::getService(AdminMenu::class);

        $menuItems = $adminMenu->getMenuItems();

        $this->assertNotEmpty($menuItems);
        $this->assertCount(5, $menuItems); // 1 section + 4 menu items

        // 验证所有项都实现了MenuItemInterface
        foreach ($menuItems as $item) {
            $this->assertInstanceOf(MenuItemInterface::class, $item);
        }
    }

    public function testGetSubMenuItems(): void
    {
        $adminMenu = self::getService(AdminMenu::class);

        $subMenuItems = $adminMenu->getSubMenuItems();

        $this->assertNotEmpty($subMenuItems);
        $this->assertCount(1, $subMenuItems); // 1 submenu with nested items

        foreach ($subMenuItems as $item) {
            $this->assertInstanceOf(MenuItemInterface::class, $item);
        }
    }

    public function testGetQuickAccessItems(): void
    {
        $adminMenu = self::getService(AdminMenu::class);

        $quickAccessItems = $adminMenu->getQuickAccessItems();

        $this->assertNotEmpty($quickAccessItems);
        $this->assertCount(3, $quickAccessItems);

        foreach ($quickAccessItems as $item) {
            $this->assertInstanceOf(MenuItemInterface::class, $item);
        }
    }

    public function testGetStatisticsItems(): void
    {
        $adminMenu = self::getService(AdminMenu::class);

        $statisticsItems = $adminMenu->getStatisticsItems();

        $this->assertNotEmpty($statisticsItems);
        $this->assertCount(2, $statisticsItems);

        foreach ($statisticsItems as $item) {
            $this->assertInstanceOf(MenuItemInterface::class, $item);
        }
    }

    public function testInvokeAddsMenuItems(): void
    {
        $adminMenu = self::getService(AdminMenu::class);

        $factory = new MenuFactory();
        $rootItem = $factory->createItem('root');

        // 显式调用 __invoke 方法而不是使用可调用语法
        $adminMenu->__invoke($rootItem);

        // 验证菜单结构
        $awsMenu = $rootItem->getChild('AWS Route53管理');
        self::assertNotNull($awsMenu, 'AWS Route53管理菜单应该被创建');

        // 验证子菜单项
        self::assertNotNull($awsMenu->getChild('AWS账户'), 'AWS账户子菜单应该存在');
        self::assertNotNull($awsMenu->getChild('托管区域'), '托管区域子菜单应该存在');
        self::assertNotNull($awsMenu->getChild('DNS记录集'), 'DNS记录集子菜单应该存在');
        self::assertNotNull($awsMenu->getChild('变更日志'), '变更日志子菜单应该存在');

        // 验证菜单项的图标
        $awsAccountMenu = $awsMenu->getChild('AWS账户');
        self::assertEquals('fas fa-user-circle', $awsAccountMenu->getAttribute('icon'));

        $hostedZoneMenu = $awsMenu->getChild('托管区域');
        self::assertEquals('fas fa-globe', $hostedZoneMenu->getAttribute('icon'));

        $recordSetMenu = $awsMenu->getChild('DNS记录集');
        self::assertEquals('fas fa-list', $recordSetMenu->getAttribute('icon'));

        $changeLogMenu = $awsMenu->getChild('变更日志');
        self::assertEquals('fas fa-history', $changeLogMenu->getAttribute('icon'));
    }

    public function testMenuItemsHaveValidStructure(): void
    {
        $adminMenu = self::getService(AdminMenu::class);

        $menuItems = $adminMenu->getMenuItems();
        $quickAccessItems = $adminMenu->getQuickAccessItems();
        $statisticsItems = $adminMenu->getStatisticsItems();

        // 验证菜单项数量和类型
        $this->assertCount(5, $menuItems); // 1 section + 4 menu items
        $this->assertCount(3, $quickAccessItems);
        $this->assertCount(2, $statisticsItems);

        // 验证所有菜单项都实现了正确的接口
        $allItems = array_merge($menuItems, $quickAccessItems, $statisticsItems);
        foreach ($allItems as $item) {
            $this->assertInstanceOf(MenuItemInterface::class, $item);
        }
    }
}
