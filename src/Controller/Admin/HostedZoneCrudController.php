<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;

/**
 * @extends AbstractCrudController<HostedZone>
 */
#[AdminCrud(routePath: '/aws-route53/hosted-zone', routeName: 'aws_route53_hosted_zone')]
final class HostedZoneCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return HostedZone::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('托管区域')
            ->setEntityLabelInPlural('托管区域')
            ->setPageTitle('index', 'Route53托管区域管理')
            ->setPageTitle('detail', '托管区域详情')
            ->setPageTitle('new', '创建托管区域')
            ->setPageTitle('edit', '编辑托管区域')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->setHelp('index', '管理Route53托管区域，包括公有和私有区域')
            ->setHelp('new', '创建新的Route53托管区域配置')
            ->setHelp('edit', '修改托管区域信息，注意同步状态的影响')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
            ->setHelp('系统生成的UUID主键')
        ;

        yield AssociationField::new('account', 'AWS账户')
            ->setRequired(true)
            ->setHelp('关联的AWS账户配置')
        ;

        yield TextField::new('awsId', 'AWS托管区域ID')
            ->setRequired(true)
            ->setMaxLength(32)
            ->setHelp('AWS Route53分配的托管区域ID')
        ;

        yield TextField::new('name', '域名')
            ->setRequired(true)
            ->setMaxLength(255)
            ->setHelp('托管区域的域名，如example.com')
        ;

        yield TextField::new('callerRef', '调用者引用')
            ->setMaxLength(255)
            ->hideOnIndex()
            ->setHelp('创建托管区域时使用的调用者引用标识')
        ;

        yield TextareaField::new('comment', '描述')
            ->setMaxLength(1000)
            ->hideOnIndex()
            ->setHelp('托管区域的描述信息')
        ;

        yield BooleanField::new('isPrivate', '私有区域')
            ->setHelp('是否为私有托管区域（VPC内部使用）')
        ;

        yield ArrayField::new('tags', 'AWS标签')
            ->hideOnIndex()
            ->setHelp('AWS资源标签信息，JSON格式')
        ;

        yield ArrayField::new('vpcAssociations', 'VPC关联')
            ->hideOnIndex()
            ->setHelp('私有托管区域的VPC关联配置，JSON格式')
        ;

        yield IntegerField::new('rrsetCount', '记录集数量')
            ->hideOnForm()
            ->setHelp('托管区域中的记录集总数')
        ;

        yield ChoiceField::new('sourceOfTruth', '数据源类型')
            ->setChoices([
                '本地数据' => 'local',
                '远程数据' => 'remote',
            ])
            ->setRequired(true)
            ->setHelp('数据的主要来源，用于同步控制')
        ;

        yield TextField::new('remoteFingerprint', '远程数据指纹')
            ->setMaxLength(64)
            ->hideOnIndex()
            ->setHelp('远程数据的指纹标识，用于变更检测')
        ;

        yield DateTimeField::new('lastSyncAt', '上次同步时间')
            ->hideOnForm()
            ->hideOnIndex()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('最后一次与AWS同步的时间')
        ;

        yield AssociationField::new('recordSets', '记录集')
            ->hideOnForm()
            ->setHelp('该托管区域下的DNS记录集')
        ;

        yield DateTimeField::new('createdAt', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('记录创建的时间')
        ;

        yield DateTimeField::new('updatedAt', '更新时间')
            ->hideOnForm()
            ->hideOnIndex()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('记录最后更新的时间')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', '域名'))
            ->add(TextFilter::new('awsId', 'AWS托管区域ID'))
            ->add('account')
            ->add(BooleanFilter::new('isPrivate', '私有区域'))
            ->add(
                ChoiceFilter::new('sourceOfTruth', '数据源类型')
                    ->setChoices([
                        '本地数据' => 'local',
                        '远程数据' => 'remote',
                    ])
            )
        ;
    }
}
