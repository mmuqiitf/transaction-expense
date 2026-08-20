<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\ProfitLossReportService;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(ProfitLossReportService $reportService): Response
    {
        $latestTransactionDate = Transaction::query()->max('transaction_date');
        $year = $latestTransactionDate
            ? CarbonImmutable::parse($latestTransactionDate)->year
            : CarbonImmutable::now()->year;
        $from = CarbonImmutable::create($year, 1, 1)->startOfDay();
        $to = CarbonImmutable::create($year, 12, 31)->endOfDay();
        $report = $reportService->build($from, $to);

        return Inertia::render('Dashboard', [
            'summary' => [
                'year' => $year,
                'income' => array_sum($report['totals']['income']),
                'expense' => array_sum($report['totals']['expense']),
                'net_income' => array_sum($report['net_income']),
            ],
            'latestTransactions' => TransactionResource::collection(
                Transaction::query()
                    ->with('chartOfAccount.category')
                    ->latest('transaction_date')
                    ->latest('id')
                    ->limit(5)
                    ->get(),
            ),
        ]);
    }
}
