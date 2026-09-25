<?php

namespace App\Modules\Identity\Enums;

enum Permission: string
{
    case PosAccess = 'pos.access';
    case SalesCreate = 'sales.create';
    case SalesDiscountsApply = 'sales.discounts.apply';
    case SalesReverse = 'sales.reverse';
    case CashSessionsOpen = 'cash_sessions.open';
    case CashSessionsClose = 'cash_sessions.close';
    case CashMovementsCreate = 'cash_movements.create';

    case HospitalityAccess = 'hospitality.access';
    case HospitalityOrdersManage = 'hospitality.orders.manage';
    case HospitalityConfigurationManage = 'hospitality.configuration.manage';
    case TicketsRedeem = 'tickets.redeem';
    case TicketsManage = 'tickets.manage';
    case PreparationAccess = 'preparation.access';
    case PreparationManage = 'preparation.manage';

    case AdministrationAccess = 'administration.access';
    case CatalogManage = 'catalog.manage';
    case UsersCashiersManage = 'users.cashiers.manage';
    case UsersWaitersManage = 'users.waiters.manage';
    case UsersRolesManage = 'users.roles.manage';
    case SalesView = 'sales.view';
    case CashSessionsView = 'cash_sessions.view';
    case ReportsView = 'reports.view';
    case AuditView = 'audit.view';
    case SettingsManage = 'settings.manage';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $permission): string => $permission->value,
            self::cases(),
        );
    }
}
