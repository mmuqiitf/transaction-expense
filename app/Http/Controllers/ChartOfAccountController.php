<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Http\Requests\ChartOfAccountIndexRequest;
use App\Http\Requests\ChartOfAccountStoreRequest;
use App\Http\Requests\ChartOfAccountUpdateRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ChartOfAccountResource;
use App\Models\Category;
use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ChartOfAccountController extends Controller
{
    public function index(ChartOfAccountIndexRequest $request): Response
    {
        $search = $request->searchTerm();
        $categoryId = $request->filter('category_id');
        $type = $request->filter('type');
        $sortColumns = [
            'code' => 'code',
            'name' => 'name',
            'created_at' => 'created_at',
        ];

        $accounts = ChartOfAccount::query()
            ->with('category')
            ->withCount('transactions')
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when(is_numeric($categoryId), function (Builder $query) use ($categoryId): void {
                $query->where('category_id', (int) $categoryId);
            })
            ->when(is_string($type), function (Builder $query) use ($type): void {
                $query->whereHas('category', fn (Builder $categoryQuery): Builder => $categoryQuery->where('type', $type));
            })
            ->orderBy($sortColumns[$request->sortColumn() ?? 'code'] ?? 'code', $request->sortDirection())
            ->paginate($request->perPage())
            ->withQueryString();

        return Inertia::render('chart-of-accounts/Index', [
            'accounts' => ChartOfAccountResource::collection($accounts),
            'categories' => CategoryResource::collection(Category::query()->orderBy('name')->get()),
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
                'type' => $type,
                'sort' => $request->sortColumn(),
                'direction' => $request->sortDirection(),
            ],
            'categoryTypes' => $this->categoryTypes(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('chart-of-accounts/Create', [
            'categories' => CategoryResource::collection(Category::query()->orderBy('name')->get()),
        ]);
    }

    public function store(ChartOfAccountStoreRequest $request): RedirectResponse
    {
        ChartOfAccount::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Chart of account created.')]);

        return to_route('chart-of-accounts.index');
    }

    public function show(ChartOfAccount $chartOfAccount): Response
    {
        return Inertia::render('chart-of-accounts/Show', [
            'account' => new ChartOfAccountResource($chartOfAccount->load('category')->loadCount('transactions')),
        ]);
    }

    public function edit(ChartOfAccount $chartOfAccount): Response
    {
        return Inertia::render('chart-of-accounts/Edit', [
            'account' => new ChartOfAccountResource($chartOfAccount->load('category')),
            'categories' => CategoryResource::collection(Category::query()->orderBy('name')->get()),
        ]);
    }

    public function update(ChartOfAccountUpdateRequest $request, ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $chartOfAccount->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Chart of account updated.')]);

        return to_route('chart-of-accounts.index');
    }

    public function destroy(ChartOfAccount $chartOfAccount): RedirectResponse
    {
        if ($chartOfAccount->transactions()->exists()) {
            return back()->withErrors(['account' => __('This chart of account cannot be deleted while it has transactions.')]);
        }

        $chartOfAccount->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Chart of account deleted.')]);

        return to_route('chart-of-accounts.index');
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
