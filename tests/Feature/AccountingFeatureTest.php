<?php

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\AccountingSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('guests must log in before accessing the shared ledger', function () {
    $this->get(route('categories.index'))->assertRedirect(route('login'));
    $this->get(route('reports.profit-loss.index'))->assertRedirect(route('login'));
});

test('authenticated users can create categories and cannot delete categories with accounts', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('categories.store'), [
            'name' => 'Gifts',
            'type' => CategoryType::Expense->value,
        ])
        ->assertRedirect(route('categories.index'));

    $category = Category::query()->where('name', 'Gifts')->firstOrFail();

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'type' => CategoryType::Expense->value,
    ]);

    ChartOfAccount::factory()->for($category)->create();

    $this->actingAs($user)
        ->delete(route('categories.destroy', $category))
        ->assertRedirect();

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});

test('category index supports server-side search and pagination query parameters', function () {
    $user = User::factory()->create();
    Category::factory()->income()->create(['name' => 'Salary']);
    Category::factory()->expense()->create(['name' => 'Meal Expense']);

    $response = $this->actingAs($user)->get(route('categories.index', [
        'search' => 'Salary',
        'page' => 2,
        'per_page' => 15,
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page): Assert => $page
        ->component('categories/Index')
        ->where('filters.search', 'Salary')
        ->where('categories.meta.current_page', 2)
        ->has('categories.data', 0));
});

test('transaction index combines search and filters on the server', function () {
    $user = User::factory()->create();
    $incomeCategory = Category::factory()->income()->create(['name' => 'Salary']);
    $expenseCategory = Category::factory()->expense()->create(['name' => 'Meals']);
    $incomeAccount = ChartOfAccount::factory()->for($incomeCategory)->create([
        'code' => '401',
        'name' => 'Salary account',
    ]);
    $expenseAccount = ChartOfAccount::factory()->for($expenseCategory)->create([
        'code' => '601',
        'name' => 'Meals account',
    ]);

    Transaction::factory()->create([
        'chart_of_account_id' => $incomeAccount->id,
        'description' => 'Monthly salary',
    ]);
    Transaction::factory()->create([
        'chart_of_account_id' => $expenseAccount->id,
        'description' => 'Monthly meal',
    ]);

    $response = $this->actingAs($user)->get(route('transactions.index', [
        'search' => 'salary',
        'filter' => ['type' => CategoryType::Income->value],
    ]));

    $response->assertInertia(fn (Assert $page): Assert => $page
        ->component('transactions/Index')
        ->where('filters.search', 'salary')
        ->where('filters.type', CategoryType::Income->value)
        ->has('transactions.data', 1)
        ->where('transactions.data.0.description', 'Monthly salary'));
});

test('seeded data includes the demo account and reproduces the profit loss report', function () {
    $this->seed(AccountingSeeder::class);

    expect(User::query()->where('email', 'demo@example.com')->exists())->toBeTrue()
        ->and(Category::count())->toBe(5)
        ->and(ChartOfAccount::count())->toBe(8)
        ->and(Transaction::count())->toBe(257)
        ->and(Transaction::query()->whereYear('transaction_date', 2026)->count())->toBe(60);

    $response = $this->actingAs(User::query()->where('email', 'demo@example.com')->firstOrFail())
        ->get(route('reports.profit-loss.index', [
            'filter' => [
                'from' => '2022-01-01',
                'to' => '2022-03-31',
            ],
        ]));

    $response->assertInertia(fn (Assert $page): Assert => $page
        ->component('reports/ProfitLoss')
        ->where('report.totals.income.2022-01', 17_500_000)
        ->where('report.totals.expense.2022-02', 4_050_000)
        ->where('report.net_income.2022-03', 10_600_000));

    $yearResponse = $this->actingAs(User::query()->where('email', 'demo@example.com')->firstOrFail())
        ->get(route('reports.profit-loss.index', ['filter' => ['year' => 2026]]));

    $yearResponse->assertInertia(fn (Assert $page): Assert => $page
        ->where('filters.year', 2026)
        ->where('report.from', '2026-01-01')
        ->where('report.to', '2026-12-31'));
});

test('profit loss report can be downloaded as an xlsx file', function () {
    $this->seed(AccountingSeeder::class);

    $response = $this->actingAs(User::query()->where('email', 'demo@example.com')->firstOrFail())
        ->get(route('reports.profit-loss.export', [
            'filter' => [
                'year' => 2026,
            ],
        ]));

    $response->assertDownload('profit-loss-2026-01-01-to-2026-12-31.xlsx');
});

test('transaction validation requires a valid account and non-negative rupiah amount', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('transactions.store'), [
        'chart_of_account_id' => 999,
        'transaction_date' => '2022-01-01',
        'amount' => -1,
    ]);

    $response->assertSessionHasErrors(['chart_of_account_id', 'amount']);
});
