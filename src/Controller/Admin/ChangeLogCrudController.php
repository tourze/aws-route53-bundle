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
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\AwsRoute53Bundle\Entity\ChangeLog;

/**
 * @extends AbstractCrudController<ChangeLog>
 */
#[AdminCrud(routePath: '/aws-route53/change-log', routeName: 'aws_route53_change_log')]
final class ChangeLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ChangeLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('变更日志')
            ->setEntityLabelInPlural('变更日志')
            ->setPageTitle('index', '操作变更日志')
            ->setPageTitle('detail', '变更日志详情')
            ->setPageTitle('new', '创建变更日志')
            ->setPageTitle('edit', '编辑变更日志')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->setHelp('index', '查看Route53操作的变更日志，追踪DNS记录的创建、修改和删除操作')
            ->setHelp('new', '手动创建变更日志记录')
            ->setHelp('edit', '修改变更日志信息，通常系统自动生成')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::EDIT, Action::DELETE)
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

        yield AssociationField::new('zone', '托管区域')
            ->setHelp('关联的托管区域，可选')
        ;

        yield TextField::new('recordKey', '记录唯一标识')
            ->setRequired(true)
            ->setMaxLength(512)
            ->setHelp('记录的唯一标识符，用于追踪变更')
        ;

        yield ChoiceField::new('action', '操作类型')
            ->setChoices([
                '创建' => 'CREATE',
                '删除' => 'DELETE',
                '更新插入' => 'UPSERT',
            ])
            ->setRequired(true)
            ->setHelp('执行的操作类型')
        ;

        yield ArrayField::new('before', '变更前数据')
            ->hideOnIndex()
            ->setHelp('变更前的数据快照，JSON格式')
        ;

        yield ArrayField::new('after', '变更后数据')
            ->hideOnIndex()
            ->setHelp('变更后的数据快照，JSON格式')
        ;

        yield TextField::new('planId', '执行计划ID')
            ->setMaxLength(36)
            ->hideOnIndex()
            ->setHelp('批量操作的计划ID，用于关联相关变更')
        ;

        yield DateTimeField::new('appliedAt', '应用时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('变更实际应用到AWS的时间')
        ;

        yield TextField::new('awsChangeId', 'AWS变更ID')
            ->setMaxLength(32)
            ->hideOnIndex()
            ->setHelp('AWS返回的变更ID')
        ;

        yield ChoiceField::new('status', '操作状态')
            ->setChoices([
                '待处理' => 'pending',
                '已应用' => 'applied',
                '失败' => 'failed',
            ])
            ->setRequired(true)
            ->setHelp('操作的执行状态')
        ;

        yield TextareaField::new('error', '错误信息')
            ->setMaxLength(5000)
            ->hideOnIndex()
            ->setHelp('操作失败时的错误信息')
        ;

        yield DateTimeField::new('createdAt', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('日志记录创建的时间')
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
            ->add(TextFilter::new('recordKey', '记录标识'))
            ->add(
                ChoiceFilter::new('action', '操作类型')
                    ->setChoices([
                        '创建' => 'CREATE',
                        '删除' => 'DELETE',
                        '更新插入' => 'UPSERT',
                    ])
            )
            ->add(
                ChoiceFilter::new('status', '操作状态')
                    ->setChoices([
                        '待处理' => 'pending',
                        '已应用' => 'applied',
                        '失败' => 'failed',
                    ])
            )
            ->add('account')
            ->add('zone')
            ->add(TextFilter::new('planId', '执行计划ID'))
            ->add(DateTimeFilter::new('createdAt', '创建时间'))
            ->add(DateTimeFilter::new('appliedAt', '应用时间'))
        ;
    }
}
