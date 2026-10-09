<?php

namespace Tally\Support\Shell;

use Tally\Models\AccountGroup;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Invoice;
use Tally\Models\Ledger;
use Tally\Models\Voucher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Breadcrumbs
{
    /**
     * @return list<array{label: string, url: ?string}>
     */
    public function items(?Request $request = null): array
    {
        $request ??= request();
        $route = $request->route()?->getName() ?? '';
        $company = $request->route('company');
        $branch = $request->route('branch');
        $financialYear = $request->route('financialYear') ?? $request->route('financial_year');

        if ($route === 'dashboard') {
            return [['label' => 'Dashboard', 'url' => null]];
        }

        $crumbs = [['label' => 'Dashboard', 'url' => tally_route('dashboard')]];

        if ($route === 'profile.edit') {
            return [...$crumbs, ['label' => 'Profile', 'url' => null]];
        }

        if (str_starts_with($route, 'account-groups.')) {
            $crumbs[] = ['label' => 'Groups', 'url' => $route === 'account-groups.index' ? null : tally_route('account-groups.index')];
            $group = $request->route('accountGroup');

            if ($route === 'account-groups.create') {
                $crumbs[] = ['label' => 'New group', 'url' => null];
            } elseif ($group instanceof AccountGroup) {
                $crumbs[] = [
                    'label' => $group->name,
                    'url' => $route === 'account-groups.show' ? null : tally_route('account-groups.show', $group),
                ];

                if ($route === 'account-groups.edit') {
                    $crumbs[] = ['label' => 'Edit', 'url' => null];
                }
            }

            return $crumbs;
        }

        if (str_starts_with($route, 'ledgers.')) {
            $crumbs[] = ['label' => 'Ledgers', 'url' => $route === 'ledgers.index' ? null : tally_route('ledgers.index')];
            $ledger = $request->route('ledger');

            if ($route === 'ledgers.create') {
                $crumbs[] = ['label' => 'New ledger', 'url' => null];
            } elseif ($ledger instanceof Ledger) {
                $crumbs[] = [
                    'label' => $ledger->name,
                    'url' => $route === 'ledgers.show' ? null : tally_route('ledgers.show', $ledger),
                ];

                if ($route === 'ledgers.edit') {
                    $crumbs[] = ['label' => 'Edit', 'url' => null];
                }
            }

            return $crumbs;
        }

        if (str_starts_with($route, 'units.')) {
            return $this->masterCrumbs($crumbs, $route, 'units', 'Units', 'New unit', $request->route('unit'));
        }

        if (str_starts_with($route, 'product-groups.')) {
            return $this->masterCrumbs($crumbs, $route, 'product-groups', 'Product groups', 'New product group', $request->route('productGroup'));
        }

        if (str_starts_with($route, 'products.')) {
            return $this->masterCrumbs($crumbs, $route, 'products', 'Products', 'New product', $request->route('product'));
        }

        if (str_starts_with($route, 'godowns.')) {
            return $this->masterCrumbs($crumbs, $route, 'godowns', 'Godowns', 'New godown', $request->route('godown'));
        }

        if (str_starts_with($route, 'boms.')) {
            return $this->masterCrumbs($crumbs, $route, 'boms', 'Bills of materials', 'New bill of materials', $request->route('bom'));
        }

        if (str_starts_with($route, 'manufacturing.')) {
            $crumbs[] = ['label' => 'Manufacturing', 'url' => $route === 'manufacturing.index' ? null : tally_route('manufacturing.index')];

            if ($route === 'manufacturing.create') {
                $crumbs[] = ['label' => 'New manufacturing journal', 'url' => null];
            } elseif ($order = $request->route('manufacturing')) {
                $crumbs[] = ['label' => $order->number, 'url' => null];
            }

            return $crumbs;
        }

        if (str_starts_with($route, 'tax-categories.')) {
            return $this->masterCrumbs($crumbs, $route, 'tax-categories', 'Tax categories', 'New tax category', $request->route('taxCategory'));
        }

        if (str_starts_with($route, 'tax-rates.')) {
            return $this->masterCrumbs($crumbs, $route, 'tax-rates', 'Tax rates', 'New tax rate', $request->route('taxRate'));
        }

        if (str_starts_with($route, 'hsn-sacs.')) {
            return $this->masterCrumbs($crumbs, $route, 'hsn-sacs', 'HSN / SAC', 'New HSN / SAC', $request->route('hsnSac'));
        }

        if (str_starts_with($route, 'tax-accounts.')) {
            return [...$crumbs, ['label' => 'Tax accounts', 'url' => null]];
        }

        foreach ([
            'cost-categories.' => ['cost-categories.index', 'Cost categories'],
            'cost-centres.' => ['cost-centres.index', 'Cost centres'],
            'budgets.' => ['budgets.index', 'Budgets'],
            'deduction-sections.' => ['deduction-sections.index', 'TDS / TCS'],
        ] as $prefix => [$index, $label]) {
            if (str_starts_with($route, $prefix)) {
                $crumbs[] = ['label' => $label, 'url' => $route === $index ? null : tally_route($index)];

                if ($route !== $index) {
                    $crumbs[] = ['label' => str_ends_with($route, '.create') ? 'New' : 'Edit', 'url' => null];
                }

                return $crumbs;
            }
        }

        if (str_starts_with($route, 'reports.gst') || str_starts_with($route, 'reports.outstanding') || str_starts_with($route, 'reports.cost-centres') || str_starts_with($route, 'reports.interest')) {
            $label = match ($route) {
                'reports.outstanding' => 'Outstanding',
                'reports.cost-centres' => 'Cost centres',
                'reports.interest' => 'Interest',
                default => 'GST summary',
            };

            return [...$crumbs, ['label' => 'Reports', 'url' => null], ['label' => $label, 'url' => null]];
        }

        if (str_starts_with($route, 'stock-movements.')) {
            return [...$crumbs, ['label' => 'Stock movements', 'url' => null]];
        }

        if (preg_match('/^stock\.(in|out|transfer|adjustment)\./', $route, $stockMatch) === 1) {
            $stockType = \Tally\Inventory\StockTransactionType::from($stockMatch[1]);
            $index = $stockType->routeName('index');
            $crumbs[] = ['label' => $stockType->label(), 'url' => $route === $index ? null : tally_route($index)];
            $stockTransaction = $request->route('stockTransaction');

            if (str_ends_with($route, '.create')) {
                $crumbs[] = ['label' => 'New', 'url' => null];
            } elseif ($stockTransaction instanceof \Tally\Models\StockTransaction) {
                $crumbs[] = ['label' => $stockTransaction->number, 'url' => null];
            }

            return $crumbs;
        }

        if (str_starts_with($route, 'vouchers.')) {
            $crumbs[] = ['label' => 'Vouchers', 'url' => $route === 'vouchers.index' ? null : tally_route('vouchers.index')];
            $voucher = $request->route('voucher');
            $createLabels = [
                'vouchers.create' => 'New journal',
                'vouchers.journal.create' => 'New journal',
                'vouchers.payment.create' => 'New payment',
                'vouchers.receipt.create' => 'New receipt',
                'vouchers.contra.create' => 'New contra',
            ];

            if (isset($createLabels[$route])) {
                $crumbs[] = ['label' => $createLabels[$route], 'url' => null];
            } elseif ($voucher instanceof Voucher) {
                $crumbs[] = [
                    'label' => $voucher->voucher_number,
                    'url' => in_array($route, ['vouchers.show', 'vouchers.print'], true) ? null : tally_route('vouchers.show', $voucher),
                ];

                if ($route === 'vouchers.edit') {
                    $crumbs[] = ['label' => 'Edit', 'url' => null];
                }

                if ($route === 'vouchers.print') {
                    $crumbs[] = ['label' => 'Print', 'url' => null];
                }
            }

            return $crumbs;
        }

        if (str_starts_with($route, 'invoices.sales.') || str_starts_with($route, 'invoices.purchase.')) {
            $purchase = str_starts_with($route, 'invoices.purchase.');
            $index = $purchase ? 'invoices.purchase.index' : 'invoices.sales.index';
            $show = $purchase ? 'invoices.purchase.show' : 'invoices.sales.show';
            $crumbs[] = ['label' => $purchase ? 'Purchase' : 'Sales', 'url' => $route === $index ? null : tally_route($index)];
            $invoice = $request->route('invoice');

            if (str_ends_with($route, '.create')) {
                $crumbs[] = ['label' => $purchase ? 'New purchase' : 'New sales', 'url' => null];
            } elseif ($invoice instanceof Invoice) {
                $crumbs[] = [
                    'label' => $invoice->invoice_number,
                    'url' => str_ends_with($route, '.show') ? null : tally_route($show, $invoice),
                ];

                if (str_ends_with($route, '.edit')) {
                    $crumbs[] = ['label' => 'Edit', 'url' => null];
                }
            }

            return $crumbs;
        }

        $stockReports = [
            'reports.stock-summary' => 'Stock summary',
            'reports.stock-ledger' => 'Stock ledger',
            'reports.godown-stock' => 'Godown-wise stock',
            'reports.stock-movements' => 'Stock movement report',
            'reports.low-stock' => 'Low stock',
            'reports.stock-valuation' => 'Stock valuation',
            'reports.expiry' => 'Expiry',
            'reports.stock-limits' => 'Stock limits',
            'reports.batch-history' => 'Batch and serial history',
            'reports.production' => 'Production',
            'reports.consumption' => 'Raw material consumption',
        ];

        if (isset($stockReports[$route])) {
            return [
                ...$crumbs,
                ['label' => 'Reports', 'url' => null],
                ['label' => $stockReports[$route], 'url' => null],
            ];
        }

        $banking = [
            'banking.accounts' => 'Bank accounts',
            'banking.transactions' => 'Bank transactions',
            'banking.reconciliation' => 'Bank reconciliation',
        ];

        if (isset($banking[$route])) {
            return [...$crumbs, ['label' => $banking[$route], 'url' => null]];
        }

        if ($route === 'branches.current') {
            return [...$crumbs, ['label' => 'Branches', 'url' => null]];
        }

        if ($route === 'financial-years.current') {
            return [...$crumbs, ['label' => 'Financial years', 'url' => null]];
        }

        if ($route === 'companies.index') {
            return [...$crumbs, ['label' => 'Companies', 'url' => null]];
        }

        if ($route === 'companies.create') {
            return [
                ...$crumbs,
                ['label' => 'Companies', 'url' => tally_route('companies.index')],
                ['label' => 'New company', 'url' => null],
            ];
        }

        if ($company instanceof Company && in_array($route, ['companies.show', 'companies.edit'], true)) {
            $crumbs[] = ['label' => 'Companies', 'url' => tally_route('companies.index')];
            $crumbs[] = [
                'label' => $company->name,
                'url' => $route === 'companies.show' ? null : tally_route('companies.show', $company),
            ];

            if ($route === 'companies.edit') {
                $crumbs[] = ['label' => 'Edit', 'url' => null];
            }

            return $crumbs;
        }

        if ($company instanceof Company && str_starts_with($route, 'acct_companies.branches')) {
            return $this->branchCrumbs($crumbs, $company, $branch instanceof Branch ? $branch : null, $route);
        }

        if ($company instanceof Company && str_starts_with($route, 'companies.financial-years')) {
            return $this->yearCrumbs($crumbs, $company, $financialYear instanceof FinancialYear ? $financialYear : null, $route);
        }

        if ($route === 'branches.current') {
            return [...$crumbs, ['label' => 'Branches', 'url' => null]];
        }

        if ($route === 'financial-years.current') {
            return [...$crumbs, ['label' => 'Financial Years', 'url' => null]];
        }

        if (str_starts_with($route, 'settings.api-tokens')) {
            return [
                ...$crumbs,
                ['label' => 'Settings', 'url' => null],
                ['label' => 'API Tokens', 'url' => null],
            ];
        }

        if (str_starts_with($route, 'audit.')) {
            return [
                ...$crumbs,
                ['label' => 'Settings', 'url' => null],
                ['label' => $route === 'audit.security' ? 'Security events' : 'Audit trail', 'url' => null],
            ];
        }

        if (str_starts_with($route, 'settings.shortcuts')) {
            return [
                ...$crumbs,
                ['label' => 'Settings', 'url' => null],
                ['label' => 'Keyboard Shortcuts', 'url' => null],
            ];
        }

        if (str_starts_with($route, 'utilities.')) {
            $label = match (true) {
                str_starts_with($route, 'utilities.import') => 'Import',
                str_starts_with($route, 'utilities.export') => 'Export',
                default => 'Backup',
            };
            $index = match ($label) {
                'Import' => 'utilities.import',
                'Export' => 'utilities.export',
                default => 'utilities.backup',
            };

            return [
                ...$crumbs,
                ['label' => 'Utilities', 'url' => null],
                ['label' => $label, 'url' => $route === $index ? null : tally_route($index)],
            ];
        }

        $page = app(Navigation::class)->placeholder((string) $request->route('page', ''));

        if ($page) {
            $crumbs[] = ['label' => $page['section'], 'url' => null];
            $crumbs[] = ['label' => $page['label'], 'url' => null];
        }

        return $crumbs;
    }

    /**
     * @param  list<array{label: string, url: ?string}>  $crumbs
     * @return list<array{label: string, url: ?string}>
     */
    private function masterCrumbs(array $crumbs, string $route, string $prefix, string $plural, string $createLabel, mixed $model): array
    {
        $crumbs[] = ['label' => $plural, 'url' => $route === $prefix.'.index' ? null : tally_route($prefix.'.index')];

        if ($route === $prefix.'.create') {
            $crumbs[] = ['label' => $createLabel, 'url' => null];
        } elseif ($model instanceof Model && filled($model->getAttribute('name'))) {
            $crumbs[] = [
                'label' => (string) $model->getAttribute('name'),
                'url' => $route === $prefix.'.show' ? null : tally_route($prefix.'.show', $model),
            ];

            if ($route === $prefix.'.edit') {
                $crumbs[] = ['label' => 'Edit', 'url' => null];
            }
        }

        return $crumbs;
    }

    /**
     * @param  list<array{label: string, url: ?string}>  $crumbs
     * @return list<array{label: string, url: ?string}>
     */
    private function branchCrumbs(array $crumbs, Company $company, ?Branch $branch, string $route): array
    {
        $crumbs[] = ['label' => $company->name, 'url' => tally_route('companies.show', $company)];
        $crumbs[] = [
            'label' => 'Branches',
            'url' => $route === 'companies.branches.index' ? null : tally_route('companies.branches.index', $company),
        ];

        if ($route === 'companies.branches.create') {
            $crumbs[] = ['label' => 'New branch', 'url' => null];
        } elseif ($branch) {
            $crumbs[] = [
                'label' => $branch->name,
                'url' => $route === 'companies.branches.show' ? null : tally_route('companies.branches.show', [$company, $branch]),
            ];

            if ($route === 'companies.branches.edit') {
                $crumbs[] = ['label' => 'Edit', 'url' => null];
            }
        }

        return $crumbs;
    }

    /**
     * @param  list<array{label: string, url: ?string}>  $crumbs
     * @return list<array{label: string, url: ?string}>
     */
    private function yearCrumbs(array $crumbs, Company $company, ?FinancialYear $financialYear, string $route): array
    {
        $crumbs[] = ['label' => $company->name, 'url' => tally_route('companies.show', $company)];
        $crumbs[] = [
            'label' => 'Financial years',
            'url' => $route === 'companies.financial-years.index' ? null : tally_route('companies.financial-years.index', $company),
        ];

        if ($route === 'companies.financial-years.create') {
            $crumbs[] = ['label' => 'New financial year', 'url' => null];
        } elseif ($financialYear) {
            $crumbs[] = [
                'label' => $financialYear->name,
                'url' => $route === 'companies.financial-years.show' ? null : tally_route('companies.financial-years.show', [$company, $financialYear]),
            ];

            if ($route === 'companies.financial-years.edit') {
                $crumbs[] = ['label' => 'Edit', 'url' => null];
            }
        }

        return $crumbs;
    }
}
