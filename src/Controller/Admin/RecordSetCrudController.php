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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\AwsRoute53Bundle\Entity\RecordSet;

/**
 * @extends AbstractCrudController<RecordSet>
 */
#[AdminCrud(routePath: '/aws-route53/record-set', routeName: 'aws_route53_record_set')]
final class RecordSetCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RecordSet::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('DNS记录集')
            ->setEntityLabelInPlural('DNS记录集')
            ->setPageTitle('index', 'DNS记录集管理')
            ->setPageTitle('detail', 'DNS记录详情')
            ->setPageTitle('new', '创建DNS记录')
            ->setPageTitle('edit', '编辑DNS记录')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->setHelp('index', '管理Route53 DNS记录集，包括A、CNAME、MX等各种记录类型')
            ->setHelp('new', '创建新的DNS记录，请选择正确的记录类型和配置参数')
            ->setHelp('edit', '修改DNS记录配置，变更将影响域名解析')
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

        yield AssociationField::new('zone', '托管区域')
            ->setRequired(true)
            ->setHelp('所属的Route53托管区域')
        ;

        yield TextField::new('name', '记录名称')
            ->setRequired(true)
            ->setMaxLength(255)
            ->setHelp('DNS记录的完整名称，如www.example.com')
        ;

        yield ChoiceField::new('type', '记录类型')
            ->setChoices([
                'A记录' => 'A',
                'AAAA记录' => 'AAAA',
                'CNAME记录' => 'CNAME',
                'MX记录' => 'MX',
                'NS记录' => 'NS',
                'PTR记录' => 'PTR',
                'SOA记录' => 'SOA',
                'SRV记录' => 'SRV',
                'TXT记录' => 'TXT',
                'CAA记录' => 'CAA',
            ])
            ->setRequired(true)
            ->setHelp('DNS记录的类型')
        ;

        yield IntegerField::new('ttl', 'TTL值')
            ->setHelp('DNS记录的生存时间（秒），为空时使用默认值')
            ->hideOnIndex()
        ;

        yield ArrayField::new('aliasTarget', '别名目标')
            ->hideOnIndex()
            ->setHelp('别名记录的目标配置，JSON格式')
        ;

        yield ArrayField::new('resourceRecords', '资源记录值')
            ->hideOnIndex()
            ->setHelp('DNS记录的值，JSON数组格式')
        ;

        yield ArrayField::new('routingPolicy', '路由策略')
            ->hideOnIndex()
            ->setHelp('Route53路由策略配置，JSON格式')
        ;

        yield TextField::new('healthCheckId', '健康检查ID')
            ->setMaxLength(36)
            ->hideOnIndex()
            ->setHelp('关联的AWS健康检查ID')
        ;

        yield TextField::new('setIdentifier', '记录集标识符')
            ->setMaxLength(128)
            ->hideOnIndex()
            ->setHelp('加权、延迟或故障转移记录的标识符')
        ;

        yield TextField::new('region', 'AWS区域')
            ->setMaxLength(50)
            ->hideOnIndex()
            ->setHelp('延迟路由策略使用的AWS区域')
        ;

        yield ArrayField::new('geoLocation', '地理位置')
            ->hideOnIndex()
            ->setHelp('地理位置路由策略配置，JSON格式')
        ;

        yield BooleanField::new('multiValueAnswer', '多值答案')
            ->hideOnIndex()
            ->setHelp('是否启用多值答案路由策略')
        ;

        yield TextField::new('localFingerprint', '本地指纹')
            ->setMaxLength(64)
            ->hideOnIndex()
            ->setHelp('本地数据的指纹标识')
        ;

        yield TextField::new('remoteFingerprint', '远程指纹')
            ->setMaxLength(64)
            ->hideOnIndex()
            ->setHelp('远程数据的指纹标识')
        ;

        yield DateTimeField::new('lastLocalModifiedAt', '本地修改时间')
            ->hideOnForm()
            ->hideOnIndex()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('本地最后修改时间')
        ;

        yield DateTimeField::new('lastSeenRemoteAt', '远程见时间')
            ->hideOnForm()
            ->hideOnIndex()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('远程最后见时间')
        ;

        yield TextField::new('lastChangeInfoId', '最后变更ID')
            ->setMaxLength(32)
            ->hideOnIndex()
            ->setHelp('最后变更信息的ID')
        ;

        yield BooleanField::new('managedBySystem', '系统管理')
            ->setHelp('是否由系统自动管理')
        ;

        yield BooleanField::new('protected', '受保护')
            ->setHelp('是否受保护，防止意外删除')
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
            ->add(TextFilter::new('name', '记录名称'))
            ->add(
                ChoiceFilter::new('type', '记录类型')
                    ->setChoices([
                        'A记录' => 'A',
                        'AAAA记录' => 'AAAA',
                        'CNAME记录' => 'CNAME',
                        'MX记录' => 'MX',
                        'NS记录' => 'NS',
                        'PTR记录' => 'PTR',
                        'SOA记录' => 'SOA',
                        'SRV记录' => 'SRV',
                        'TXT记录' => 'TXT',
                        'CAA记录' => 'CAA',
                    ])
            )
            ->add('zone')
            ->add(BooleanFilter::new('managedBySystem', '系统管理'))
            ->add(BooleanFilter::new('protected', '受保护'))
        ;
    }
}
