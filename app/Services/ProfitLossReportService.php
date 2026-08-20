<?php

namespace App\Services;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ProfitLossReportService
{
    /**
     * @return array{
     *     from: string,
     *     to: string,
     *     months: list<array{key: string, label: string}>,
     *     rows: list<array{id: int, name: string, type: string, amounts: array<string, int>, total: int}>,
     *     totals: array{income: array<string, int>, expense: array<string, int>},
     *     net_income: array<string, int>
     * }
     */
    public function build(CarbonImmutable $from, CarbonImmutable $to, ?int $categoryId = null): array
    {
        $months = $this->monthsBetween($from, $to);
        $monthKeys = array_column($months, 'key');

        $categories = Category::query()
            ->when($categoryId, fn ($query) => $query->whereKey($categoryId))
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        /** @var array<int, array{id: int, name: string, type: string, amounts: array<string, int>, total: int}> $rows */
        $rows = [];

        foreach ($categories as $category) {
            $rows[$category->id] = [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type->value,
                'amounts' => array_fill_keys($monthKeys, 0),
                'total' => 0,
            ];
        }

        $monthExpression = $this->monthExpression();
        $transactionTable = (new Transaction)->getTable();
        $chartOfAccountTable = (new ChartOfAccount)->getTable();
        $categoryTable = (new Category)->getTable();

        $aggregates = DB::table("{$transactionTable} as transactions")
            ->join("{$chartOfAccountTable} as chart_of_accounts", 'chart_of_accounts.id', '=', 'transactions.chart_of_account_id')
            ->join("{$categoryTable} as categories", 'categories.id', '=', 'chart_of_accounts.category_id')
            ->whereBetween('transactions.transaction_date', [$from->toDateString(), $to->toDateString()])
            ->when($categoryId, fn ($query) => $query->where('categories.id', $categoryId))
            ->selectRaw("{$monthExpression} as month, categories.id as category_id, SUM(transactions.amount) as amount")
            ->groupByRaw("{$monthExpression}, categories.id")
            ->get();

        foreach ($aggregates as $aggregate) {
            $categoryId = (int) $aggregate->category_id;
            $month = (string) $aggregate->month;

            if (! isset($rows[$categoryId]['amounts'][$month])) {
                continue;
            }

            $rows[$categoryId]['amounts'][$month] = (int) $aggregate->amount;
        }

        /** @var array{income: array<string, int>, expense: array<string, int>} $totals */
        $totals = [
            'income' => array_fill_keys($monthKeys, 0),
            'expense' => array_fill_keys($monthKeys, 0),
        ];

        foreach ($rows as &$row) {
            $row['total'] = array_sum($row['amounts']);

            foreach ($row['amounts'] as $month => $amount) {
                if ($row['type'] === CategoryType::Income->value) {
                    $totals['income'][$month] += $amount;
                } else {
                    $totals['expense'][$month] += $amount;
                }
            }
        }

        unset($row);

        /** @var array<string, int> $netIncome */
        $netIncome = array_fill_keys($monthKeys, 0);

        foreach ($monthKeys as $month) {
            $netIncome[$month] = $totals['income'][$month] - $totals['expense'][$month];
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'months' => $months,
            'rows' => array_values($rows),
            'totals' => $totals,
            'net_income' => $netIncome,
        ];
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function monthsBetween(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $months = [];
        $current = $from->startOfMonth();
        $last = $to->startOfMonth();

        while ($current <= $last) {
            $months[] = [
                'key' => $current->format('Y-m'),
                'label' => $current->format('M Y'),
            ];
            $current = $current->addMonth();
        }

        return $months;
    }

    /** @return literal-string */
    private function monthExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', transactions.transaction_date)",
            'pgsql' => "to_char(transactions.transaction_date, 'YYYY-MM')",
            default => "DATE_FORMAT(transactions.transaction_date, '%Y-%m')",
        };
    }
}
