<?php

namespace Tally\Support\Shell;

use Illuminate\Support\Facades\Route;

class TallyPanel
{
    /**
     * @return list<array{key: string, label: string, href: ?string, action: ?string, current: bool}>
     */
    public static function buttons(): array
    {
        $route = request()->route()?->getName() ?? '';
        $current = self::currentKey($route);

        if ($route === 'dashboard' && request('screen') !== 'tiles') {
            return [
                self::button('F2', 'Date', null, 'date', false),
                self::button('Alt+F2', 'Period', null, 'period', false),
                self::button('F3', 'Company', null, 'company', false),
            ];
        }

        if (self::isVoucher($route)) {
            $requestStat = request()->boolean('stat') && $route === 'vouchers.payment.create';
            $requestMemo = request()->boolean('memo') && $route === 'vouchers.journal.create';
            $requestReversing = request()->boolean('reversing') && $route === 'vouchers.journal.create';

            return [
                self::button('F2', 'Date', null, 'date', false),
                self::button('Alt+F2', 'Period', null, 'period', false),
                self::button('F3', 'Company', null, 'company', false),
                self::button('F4', 'Contra', 'vouchers.contra.create', null, $current === 'F4'),
                self::button('F5', 'Payment', 'vouchers.payment.create', null, $current === 'F5'),
                self::button('F6', 'Receipt', 'vouchers.receipt.create', null, $current === 'F6'),
                self::button('F7', 'Journal', 'vouchers.journal.create', null, $current === 'F7'),
                self::button('F8', 'Sales', 'invoices.sales.create', null, $current === 'F8'),
                self::button('F9', 'Purchase', 'invoices.purchase.create', null, $current === 'F9'),
                self::button('F10', 'Credit Note', 'invoices.credit-note.create', null, $current === 'F10'),
                self::button('Ctrl+F9', 'Debit Note', 'invoices.debit-note.create', null, $current === 'Ctrl+F9'),
                self::button('L', 'Post-Dated', null, 'post-dated', false),
                self::button('Ctrl+L', 'Optional', null, 'optional', false),
                self::button('Alt+F5', 'Stat Payment', 'vouchers.payment.create', null, $requestStat, ['stat' => 1]),
                self::button('Ctrl+F7', 'Memos', 'vouchers.journal.create', null, $requestMemo, ['memo' => 1]),
                self::button('Alt+F6', 'Reversing Journal', 'vouchers.journal.create', null, $requestReversing, ['reversing' => 1]),
                self::button('Alt+F11', 'Voucher Class', 'vouchers.classes.index', null, $route === 'vouchers.classes.index'),
                self::button('F11', 'Features', 'companies.features', null, false),
                self::button('F12', 'Configure', 'preferences.edit', null, false),
            ];
        }

        if (str_starts_with($route, 'reports.') || ($route === 'dashboard' && request('screen') === 'tiles')) {
            return [
                self::button('F2', 'Date', null, 'date', false),
                self::button('Alt+F2', 'Period', null, 'period', false),
                self::button('F3', 'Company', null, 'company', false),
                self::button('F10', 'More Reports', 'reports.menu', null, $route === 'reports.menu'),
                self::button('F12', 'Configure', 'preferences.edit', null, false),
            ];
        }

        if ($route === 'companies.features') {
            return [
                self::button('F2', 'Date', null, 'date', false),
                self::button('Alt+F2', 'Period', null, 'period', false),
                self::button('F3', 'Company', null, 'company', false),
                self::button('F12', 'Configure', 'preferences.edit', null, false),
            ];
        }

        if ($route === 'masters.menu' || $route === 'masters.create-menu' || self::isMaster($route)) {
            return [
                self::button('F2', 'Date', null, 'date', false),
                self::button('Alt+F2', 'Period', null, 'period', false),
                self::button('F3', 'Company', null, 'company', false),
                self::button('F10', 'Other Masters', 'masters.menu', null, false),
                self::button('F12', 'Configure', 'preferences.edit', null, false),
            ];
        }

        return [
            self::button('F2', 'Date', null, 'date', false),
            self::button('Alt+F2', 'Period', null, 'period', false),
            self::button('F3', 'Company', null, 'company', false),
            self::button('F12', 'Configure', 'preferences.edit', null, false),
        ];
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public static function footer(): array
    {
        $route = request()->route()?->getName() ?? '';
        $items = [['key' => 'Q', 'label' => 'Quit']];

        if (self::isVoucher($route) || self::isMaster($route) || $route === 'companies.features' || $route === 'masters.menu' || $route === 'masters.create-menu') {
            $items[] = ['key' => 'A', 'label' => 'Accept'];
            $items[] = ['key' => 'D', 'label' => 'Delete'];
        }

        if (self::isVoucher($route)) {
            $items[] = ['key' => 'X', 'label' => 'Cancel Vch'];
        }

        return $items;
    }

    public static function exception(): ?string
    {
        return null;
    }

    private static function isVoucher(string $route): bool
    {
        return str_starts_with($route, 'vouchers.')
            || str_starts_with($route, 'invoices.')
            || str_starts_with($route, 'stock.');
    }

    private static function isMaster(string $route): bool
    {
        foreach (['ledgers.', 'account-groups.', 'parties.', 'products.', 'product-groups.', 'units.', 'godowns.', 'cost-centres.', 'cost-categories.', 'hsn-sacs.', 'tax-rates.', 'tax-accounts.', 'boms.', 'budgets.', 'deduction-sections.', 'currencies.', 'voucher-types.', 'gst-registrations.', 'merchant-profiles.', 'employees.', 'pay-heads.'] as $prefix) {
            if (str_starts_with($route, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private static function currentKey(string $route): ?string
    {
        return match (true) {
            str_contains($route, 'contra') => 'F4',
            str_contains($route, 'payment') => 'F5',
            str_contains($route, 'receipt') => 'F6',
            str_contains($route, 'journal') => 'F7',
            str_contains($route, 'invoices.sales') => 'F8',
            str_contains($route, 'invoices.purchase') => 'F9',
            str_contains($route, 'credit-note') => 'F10',
            str_contains($route, 'debit-note') => 'Ctrl+F9',
            $route === 'vouchers.other' => 'F10',
            default => null,
        };
    }

    public static function catalogue(string $title, string $route, string $section, string $mode = 'index'): \Illuminate\Contracts\View\View
    {
        $groups = self::groups($section, $mode);
        $chosen = request()->string('group')->toString();

        if ($route === 'reports.menu' && $chosen === '') {
            return view('tally::shell.catalogue', [
                'title' => $title,
                'groups' => $groups,
                'items' => [],
                'back' => tally_route('dashboard'),
            ]);
        }

        if ($chosen !== '' && isset($groups[$chosen])) {
            return view('tally::shell.catalogue', [
                'title' => $chosen,
                'groups' => [$chosen => $groups[$chosen]],
                'items' => [],
                'back' => tally_route($route),
            ]);
        }

        $items = [];

        foreach (array_keys($groups) as $heading) {
            $items[] = ['label' => $heading, 'route' => $route, 'params' => ['group' => $heading]];
        }

        return view('tally::shell.catalogue', [
            'title' => $title,
            'groups' => [],
            'items' => $items,
            'back' => tally_route('dashboard'),
        ]);
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public static function groups(string $sectionKey, string $mode = 'index'): array
    {
        $section = collect(config('navigation.sections'))->firstWhere('key', $sectionKey);
        $children = $section['children'] ?? [];

        if ($sectionKey === 'reports') {
            $groups = [
                'Accounting' => [],
                'Inventory' => [],
                'Statutory' => [],
                'Payroll' => [],
                'Exception' => [],
            ];
            $priority = [
                'Accounting' => ['trial-balance', 'day-book', 'cash-flow', 'funds-flow', 'account-books', 'statements-of-accounts', 'ledger-report', 'profit-and-loss', 'balance-sheet', 'ratio-analysis'],
                'Inventory' => ['inventory-books', 'statements-of-inventory', 'stock-summary', 'stock-ledger'],
                'Exception' => ['exceptions', 'analysis', 'edit-log'],
            ];

            $shown = [
                'trial-balance', 'day-book', 'cash-flow', 'funds-flow', 'account-books', 'statements-of-accounts',
                'ledger-report', 'profit-and-loss', 'balance-sheet', 'ratio-analysis',
                'inventory-books', 'statements-of-inventory', 'stock-summary', 'stock-ledger',
                'gst', 'gstr-1', 'gstr-3b',
                'payroll-report',
                'exceptions', 'analysis', 'edit-log',
            ];

            foreach ($children as $child) {
                $key = $child['key'] ?? '';

                if (! in_array($key, $shown, true)) {
                    continue;
                }

                if ($key === 'payroll-report') {
                    $child['label'] = 'Payroll Reports';
                    $groups['Payroll'][] = $child;

                    continue;
                }

                $group = match (true) {
                    in_array($key, ['exceptions', 'analysis', 'edit-log'], true) => 'Exception',
                    $key === 'gst' || ($child['domain'] ?? '') === 'tax' => 'Statutory',
                    ($child['domain'] ?? '') === 'inventory' || in_array($key, ['production', 'consumption'], true) => 'Inventory',
                    default => 'Accounting',
                };
                $groups[$group][] = $child;
            }

            foreach ($groups as $name => $rows) {
                $order = array_flip($priority[$name] ?? []);
                usort($rows, function (array $left, array $right) use ($order): int {
                    $leftKey = $order[$left['key'] ?? ''] ?? 1000;
                    $rightKey = $order[$right['key'] ?? ''] ?? 1000;

                    return $leftKey <=> $rightKey;
                });
                $groups[$name] = $rows;
            }

            if (tally_route_has('audit.index')) {
                $groups['Exception'][] = ['label' => 'Audit Trail', 'route' => 'audit.index'];
            }

            return array_filter($groups);
        }

        $labels = [
            'accounting' => 'Accounting Masters',
            'inventory' => 'Inventory Masters',
            'tax' => 'Statutory Masters',
        ];
        $groups = [];

        foreach ($children as $child) {
            $item = $child;

            if ($mode === 'create') {
                $routeName = (string) ($child['route'] ?? '');

                if (! str_ends_with($routeName, '.index')) {
                    continue;
                }

                $create = preg_replace('/\.index$/', '.create', $routeName);

                if (! is_string($create) || ! tally_route_has($create)) {
                    continue;
                }

                $item['route'] = $create;
            }

            $heading = $labels[$child['domain'] ?? ''] ?? 'Masters';
            $groups[$heading][] = $item;
        }

        if ($mode !== 'create' && tally_route_has('companies.features')) {
            $groups['Accounting Masters'][] = ['label' => 'Company Features', 'route' => 'companies.features'];
        }

        return array_filter($groups);
    }

    /**
     * @return array{key: string, label: string, href: ?string, action: ?string, current: bool}
     */
    /**
     * @param  array<string, mixed>  $params
     * @return array{key: string, label: string, href: ?string, action: ?string, current: bool}
     */
    private static function button(string $key, string $label, ?string $route, ?string $action, bool $current, array $params = []): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'href' => $route && tally_route_has($route) ? tally_route($route, $params) : null,
            'action' => $action,
            'current' => $current,
        ];
    }
}
