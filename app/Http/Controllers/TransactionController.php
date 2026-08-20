<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Http\Requests\TransactionImportRequest;
use App\Http\Requests\TransactionIndexRequest;
use App\Http\Requests\TransactionStoreRequest;
use App\Http\Requests\TransactionUpdateRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ChartOfAccountResource;
use App\Http\Resources\TransactionResource;
use App\Imports\TransactionsImport;
use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransactionController extends Controller
{
    public function index(TransactionIndexRequest $request): Response
    {
        $search = $request->searchTerm();
        $from = $request->filter('from');
        $to = $request->filter('to');
        $type = $request->filter('type');
        $categoryId = $request->filter('category_id');
        $accountId = $request->filter('chart_of_account_id');
        $sortColumns = [
            'transaction_date' => 'transaction_date',
            'amount' => 'amount',
            'created_at' => 'created_at',
        ];

        $transactions = Transaction::query()
            ->with('chartOfAccount.category')
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhereHas('chartOfAccount', function (Builder $accountQuery) use ($search): void {
                            $accountQuery->where('code', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%")
                                ->orWhereHas('category', fn (Builder $categoryQuery): Builder => $categoryQuery->where('name', 'like', "%{$search}%"));
                        });
                });
            })
            ->when(is_string($from), fn (Builder $query): Builder => $query->whereDate('transaction_date', '>=', $from))
            ->when(is_string($to), fn (Builder $query): Builder => $query->whereDate('transaction_date', '<=', $to))
            ->when(is_string($type), fn (Builder $query): Builder => $query->whereHas('chartOfAccount.category', fn (Builder $categoryQuery): Builder => $categoryQuery->where('type', $type)))
            ->when(is_numeric($categoryId), fn (Builder $query): Builder => $query->whereHas('chartOfAccount', fn (Builder $accountQuery): Builder => $accountQuery->where('category_id', (int) $categoryId)))
            ->when(is_numeric($accountId), fn (Builder $query): Builder => $query->where('chart_of_account_id', (int) $accountId))
            ->orderBy($sortColumns[$request->sortColumn() ?? 'transaction_date'] ?? 'transaction_date', $request->sortDirection())
            ->paginate($request->perPage())
            ->withQueryString();

        return Inertia::render('transactions/Index', [
            'transactions' => TransactionResource::collection($transactions),
            'categories' => CategoryResource::collection(Category::query()->orderBy('name')->get()),
            'accounts' => ChartOfAccountResource::collection(ChartOfAccount::query()->with('category')->orderBy('code')->get()),
            'filters' => [
                'search' => $search,
                'from' => $from,
                'to' => $to,
                'type' => $type,
                'category_id' => $categoryId,
                'chart_of_account_id' => $accountId,
                'sort' => $request->sortColumn(),
                'direction' => $request->sortDirection(),
            ],
            'categoryTypes' => $this->categoryTypes(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('transactions/Create', $this->formOptions());
    }

    public function store(TransactionStoreRequest $request): RedirectResponse
    {
        Transaction::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction created.')]);

        return to_route('transactions.index');
    }

    public function show(Transaction $transaction): Response
    {
        return Inertia::render('transactions/Show', [
            'transaction' => new TransactionResource($transaction->load('chartOfAccount.category')),
        ]);
    }

    public function edit(Transaction $transaction): Response
    {
        return Inertia::render('transactions/Edit', [
            'transaction' => new TransactionResource($transaction->load('chartOfAccount.category')),
            ...$this->formOptions(),
        ]);
    }

    public function update(TransactionUpdateRequest $request, Transaction $transaction): RedirectResponse
    {
        $transaction->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction updated.')]);

        return to_route('transactions.index');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $transaction->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction deleted.')]);

        return to_route('transactions.index');
    }

    public function import(TransactionImportRequest $request): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');
        $import = new TransactionsImport;

        DB::transaction(function () use ($import, $file): void {
            Excel::import($import, $file);
        });

        $count = $import->getImportedCount();

        activity()
            ->causedBy($request->user())
            ->withProperties(['count' => $count, 'filename' => $file->getClientOriginalName()])
            ->log("Imported {$count} transactions from {$file->getClientOriginalName()}");

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':count transactions imported successfully.', ['count' => $count])]);

        return to_route('transactions.index');
    }

    public function downloadSample(): BinaryFileResponse
    {
        return response()->download(
            resource_path('templates/sample-transactions-import.csv'),
            'sample-transactions-import.csv',
        );
    }

    /**
     * @return array{
     *     categories: AnonymousResourceCollection,
     *     accounts: AnonymousResourceCollection,
     *     categoryTypes: list<array{value: string, label: string}>
     * }
     */
    private function formOptions(): array
    {
        return [
            'categories' => CategoryResource::collection(Category::query()->orderBy('name')->get()),
            'accounts' => ChartOfAccountResource::collection(ChartOfAccount::query()->with('category')->orderBy('code')->get()),
            'categoryTypes' => $this->categoryTypes(),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function categoryTypes(): array
    {
        return array_map(
            fn (CategoryType $categoryType): array => [
                'value' => $categoryType->value,
                'label' => ucfirst($categoryType->value),
            ],
            CategoryType::cases(),
        );
    }
}
