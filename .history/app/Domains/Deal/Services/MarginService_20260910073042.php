<?php

namespace App\Domains\Deal\Services;

use App\Domains\Deal\Models\Deal;
use App\Domains\Deal\Models\DealExpense;
use App\Domains\Sales\Models\Invoice;
use Illuminate\Support\Collection;

class MarginService
{
    /**
     * net_margin = sale − landed/purchase − discounts − deal costs
     *
     * @return array{sale: float, landed: float, discounts: float, deal_costs: float, net_margin: float}
     */
    public function forDeal(Deal $deal): array
    {
        $deal->loadMissing(['expenses.expenseType', 'invoice.items.product', 'order']);

        $sale = (float) $deal->sale_amount;
        $discounts = (float) $deal->discount_amount;
        $landed = (float) $deal->landed_cost;

        if ($deal->invoice) {
            $sale = (float) $deal->invoice->grand_total;
            $discounts = (float) $deal->invoice->discount_amount;
            $landed = $this->estimateInvoiceLandedCost($deal->invoice);
        }

        $dealCosts = $this->approvedDealCosts($deal->expenses);
        $netMargin = round($sale - $landed - $discounts - $dealCosts, 2);

        return [
            'sale' => round($sale, 2),
            'landed' => round($landed, 2),
            'discounts' => round($discounts, 2),
            'deal_costs' => round($dealCosts, 2),
            'net_margin' => $netMargin,
        ];
    }

    public function refreshDeal(Deal $deal): Deal
    {
        $margin = $this->forDeal($deal);

        $deal->update([
            'sale_amount' => $margin['sale'],
            'landed_cost' => $margin['landed'],
            'discount_amount' => $margin['discounts'],
            'deal_cost_total' => $margin['deal_costs'],
            'net_margin' => $margin['net_margin'],
        ]);

        return $deal->fresh(['customer', 'invoice', 'order', 'expenses.expenseType']);
    }

    public function estimateInvoiceLandedCost(Invoice $invoice): float
    {
        $invoice->loadMissing('items.product');

        return round($invoice->items->sum(function ($item) {
            $unit = (float) ($item->product?->purchase_price ?? 0);

            return $unit * (float) $item->quantity;
        }), 2);
    }

    /**
     * @param  Collection<int, DealExpense>|iterable<DealExpense>  $expenses
     */
    public function approvedDealCosts(iterable $expenses): float
    {
        return round(collect($expenses)
            ->filter(fn (DealExpense $expense) => in_array($expense->status, ['approved', 'posted'], true))
            ->filter(function (DealExpense $expense) {
                $treatment = $expense->expenseType?->accounting_treatment ?? 'deal_expense';

                return in_array($treatment, ['deal_expense', 'trade_discount'], true);
            })
            ->sum(fn (DealExpense $expense) => (float) $expense->amount), 2);
    }
}
