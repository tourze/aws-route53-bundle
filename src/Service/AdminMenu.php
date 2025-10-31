<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Service;

use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use Knp\Menu\ItemInterface;
use Tourze\AwsRoute53Bundle\Controller\Admin\AwsAccountCrudController;
use Tourze\AwsRoute53Bundle\Controller\Admin\ChangeLogCrudController;
use Tourze\AwsRoute53Bundle\Controller\Admin\HostedZoneCrudController;
use Tourze\AwsRoute53Bundle\Controller\Admin\RecordSetCrudController;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Entity\ChangeLog;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;
use Tourze\AwsRoute53Bundle\Entity\RecordSet;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    /**
     * 实现 MenuProviderInterface 接口方法
     */
    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('AWS Route53管理')) {
            $item->addChild('AWS Route53管理');
        }

        $awsMenu = $item->getChild('AWS Route53管理');
        if (null === $awsMenu) {
            return;
        }

        $awsMenu->addChild('AWS账户')
            ->setUri($this->linkGenerator->getCurdListPage(AwsAccount::class))
            ->setAttribute('icon', 'fas fa-user-circle')
        ;

        $awsMenu->addChild('托管区域')
            ->setUri($this->linkGenerator->getCurdListPage(HostedZone::class))
            ->setAttribute('icon', 'fas fa-globe')
        ;

        $awsMenu->addChild('DNS记录集')
            ->setUri($this->linkGenerator->getCurdListPage(RecordSet::class))
            ->setAttribute('icon', 'fas fa-list')
        ;

        $awsMenu->addChild('变更日志')
            ->setUri($this->linkGenerator->getCurdListPage(ChangeLog::class))
            ->setAttribute('icon', 'fas fa-history')
        ;
    }

    /**
     * 获取AWS Route53管理菜单项
     *
     * @return MenuItemInterface[]
     */
    public function getMenuItems(): array
    {
        return [
            MenuItem::section('AWS Route53管理', 'fas fa-cloud-aws')
                ->setPermission('ROLE_USER'),

            MenuItem::linkToCrud('AWS账户', 'fas fa-user-circle', AwsAccountCrudController::getEntityFqcn())
                ->setController(AwsAccountCrudController::class)
                ->setPermission('ROLE_USER'),

            MenuItem::linkToCrud('托管区域', 'fas fa-globe', HostedZoneCrudController::getEntityFqcn())
                ->setController(HostedZoneCrudController::class)
                ->setPermission('ROLE_USER'),

            MenuItem::linkToCrud('DNS记录集', 'fas fa-list', RecordSetCrudController::getEntityFqcn())
                ->setController(RecordSetCrudController::class)
                ->setPermission('ROLE_USER'),

            MenuItem::linkToCrud('变更日志', 'fas fa-history', ChangeLogCrudController::getEntityFqcn())
                ->setController(ChangeLogCrudController::class)
                ->setPermission('ROLE_USER'),
        ];
    }

    /**
     * 获取子菜单项（用于嵌套菜单结构）
     *
     * @return MenuItemInterface[]
     */
    public function getSubMenuItems(): array
    {
        return [
            MenuItem::subMenu('AWS Route53', 'fas fa-cloud-aws')
                ->setSubItems([
                    MenuItem::linkToCrud('AWS账户', 'fas fa-user-circle', AwsAccountCrudController::getEntityFqcn())
                        ->setController(AwsAccountCrudController::class),

                    MenuItem::linkToCrud('托管区域', 'fas fa-globe', HostedZoneCrudController::getEntityFqcn())
                        ->setController(HostedZoneCrudController::class),

                    MenuItem::linkToCrud('DNS记录集', 'fas fa-list', RecordSetCrudController::getEntityFqcn())
                        ->setController(RecordSetCrudController::class),

                    MenuItem::linkToCrud('变更日志', 'fas fa-history', ChangeLogCrudController::getEntityFqcn())
                        ->setController(ChangeLogCrudController::class),
                ])
                ->setPermission('ROLE_USER'),
        ];
    }

    /**
     * 获取快捷菜单项（用于仪表板或快速访问）
     *
     * @return MenuItemInterface[]
     */
    public function getQuickAccessItems(): array
    {
        return [
            MenuItem::linkToCrud('管理AWS账户', 'fas fa-user-circle', AwsAccountCrudController::getEntityFqcn())
                ->setController(AwsAccountCrudController::class)
                ->setPermission('ROLE_ADMIN'),

            MenuItem::linkToCrud('查看DNS记录', 'fas fa-list', RecordSetCrudController::getEntityFqcn())
                ->setController(RecordSetCrudController::class)
                ->setPermission('ROLE_USER'),

            MenuItem::linkToCrud('变更历史', 'fas fa-history', ChangeLogCrudController::getEntityFqcn())
                ->setController(ChangeLogCrudController::class)
                ->setPermission('ROLE_USER'),
        ];
    }

    /**
     * 获取统计相关菜单项
     *
     * @return MenuItemInterface[]
     */
    public function getStatisticsItems(): array
    {
        return [
            MenuItem::linkToUrl('AWS Route53统计', 'fas fa-chart-bar', '/admin/aws-route53/statistics')
                ->setPermission('ROLE_ADMIN'),

            MenuItem::linkToUrl('域名解析监控', 'fas fa-monitor-heart-rate', '/admin/aws-route53/monitoring')
                ->setPermission('ROLE_USER'),
        ];
    }
}
