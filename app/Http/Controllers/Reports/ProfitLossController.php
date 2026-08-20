<?php

namespace App\Http\Controllers\Reports;

use App\Exports\ProfitLossExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProfitLossReportRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\ProfitLossReportService;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfitLossController extends Controller
{
    public function index(ProfitLossReportRequest $request, ProfitLossReportService $reportService): Response
    {
        $report = $reportService->build($request->fromDate(), $request->toDate(), $request->categoryId());

        return Inertia::render('reports/ProfitLoss', [
            'report' => $report,
            'categories' => CategoryResource::collection(Category::query()->orderBy('name')->get()),
            'years' => range(2022, max(2026, now()->year)),
            'filters' => [
                'from' => $request->fromDate()->toDateString(),
                'to' => $request->toDate()->toDateString(),
                'year' => $request->selectedYear(),
                'category_id' => $request->categoryId(),
            ],
        ]);
    }

    public function export(ProfitLossReportRequest $request, ProfitLossReportService $reportService): BinaryFileResponse
    {
        $report = $reportService->build($request->fromDate(), $request->toDate(), $request->categoryId());
        $filename = "profit-loss-{$report['from']}-to-{$report['to']}.xlsx";

        return Excel::download(new ProfitLossExport($report), $filename);
    }
}
