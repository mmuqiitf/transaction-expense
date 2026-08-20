<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Http\Requests\CategoryIndexRequest;
use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(CategoryIndexRequest $request): Response
    {
        $search = $request->searchTerm();
        $type = $request->filter('type');
        $sortColumns = [
            'name' => 'name',
            'type' => 'type',
            'created_at' => 'created_at',
        ];

        $categories = Category::query()
            ->withCount('chartOfAccounts')
            ->when($search, function (Builder $query, string $search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when(is_string($type), function (Builder $query) use ($type): void {
                $query->where('type', $type);
            })
            ->orderBy($sortColumns[$request->sortColumn() ?? 'name'] ?? 'name', $request->sortDirection())
            ->paginate($request->perPage())
            ->withQueryString();

        return Inertia::render('categories/Index', [
            'categories' => CategoryResource::collection($categories),
            'filters' => [
                'search' => $search,
                'type' => $type,
                'sort' => $request->sortColumn(),
                'direction' => $request->sortDirection(),
            ],
            'categoryTypes' => $this->categoryTypes(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('categories/Create', [
            'categoryTypes' => $this->categoryTypes(),
        ]);
    }

    public function store(CategoryStoreRequest $request): RedirectResponse
    {
        Category::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return to_route('categories.index');
    }

    public function show(Category $category): Response
    {
        return Inertia::render('categories/Show', [
            'category' => new CategoryResource($category->loadCount('chartOfAccounts')),
        ]);
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('categories/Edit', [
            'category' => new CategoryResource($category),
            'categoryTypes' => $this->categoryTypes(),
        ]);
    }

    public function update(CategoryUpdateRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return to_route('categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->chartOfAccounts()->exists()) {
            return back()->withErrors(['category' => __('This category cannot be deleted while it has chart-of-account entries.')]);
        }

        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

        return to_route('categories.index');
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
