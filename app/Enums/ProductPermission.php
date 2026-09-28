<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductPermission: string
{
    case View = 'products.view';
    case Create = 'products.create';
    case Update = 'products.update';
    case Archive = 'products.archive';
    case PricesManage = 'products.prices.manage';
    case BarcodesManage = 'products.barcodes.manage';
    case SuppliersManage = 'products.suppliers.manage';
    case CostView = 'products.cost.view';
    case ClassificationsManage = 'products.classifications.manage';

    public function displayName(): string
    {
        return match ($this) {
            self::View => 'View Products',
            self::Create => 'Create Products',
            self::Update => 'Update Products',
            self::Archive => 'Archive and Restore Products',
            self::PricesManage => 'Manage Product Selling Prices',
            self::BarcodesManage => 'Manage Product Barcodes',
            self::SuppliersManage => 'Manage Product Suppliers',
            self::CostView => 'View Product Costs',
            self::ClassificationsManage => 'Manage Product Classifications',
        };
    }
}
