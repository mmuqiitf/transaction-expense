<?php

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

test('guests cannot access audit logs', function () {
    $this->get(route('audit-logs.index'))->assertRedirect(route('login'));
});

test('authenticated users can access audit logs page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('audit-logs.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page): Assert => $page
        ->component('audit-logs/Index')
        ->has('logs.data')
        ->has('subjectTypes')
        ->has('events')
        ->has('users'));
});

test('category lifecycle records activity log with authenticated causer', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('categories.store'), [
        'name' => 'Investments',
        'type' => CategoryType::Income->value,
    ]);

    $category = Category::query()->where('name', 'Investments')->firstOrFail();

    $activity = Activity::forSubject($category)->first();
    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe('created')
        ->and($activity->causer_id)->toBe($user->id)
        ->and($activity->attribute_changes['attributes']['name'])->toBe('Investments');

    $this->actingAs($user)->put(route('categories.update', $category), [
        'name' => 'Crypto & Investments',
        'type' => CategoryType::Income->value,
    ]);

    $updateActivity = Activity::forSubject($category)->where('event', 'updated')->first();
    expect($updateActivity)->not->toBeNull()
        ->and($updateActivity->attribute_changes['old']['name'])->toBe('Investments')
        ->and($updateActivity->attribute_changes['attributes']['name'])->toBe('Crypto & Investments');
});

test('transaction lifecycle records activity logs', function () {
    $user = User::factory()->create();
    $category = Category::factory()->income()->create();
    $account = ChartOfAccount::factory()->for($category)->create();

    $this->actingAs($user)->post(route('transactions.store'), [
        'chart_of_account_id' => $account->id,
        'transaction_date' => '2026-08-20',
        'amount' => 500000,
        'description' => 'Freelance project',
    ]);

    $transaction = Transaction::query()->where('description', 'Freelance project')->firstOrFail();

    $createActivity = Activity::forSubject($transaction)->where('event', 'created')->first();
    expect($createActivity)->not->toBeNull()
        ->and($createActivity->causer_id)->toBe($user->id)
        ->and($createActivity->attribute_changes['attributes']['amount'])->toBe(500000);

    $this->actingAs($user)->delete(route('transactions.destroy', $transaction));

    $deleteActivity = Activity::forSubject($transaction)->where('event', 'deleted')->first();
    expect($deleteActivity)->not->toBeNull()
        ->and($deleteActivity->causer_id)->toBe($user->id);
});

test('audit log search and filters work correctly', function () {
    $user = User::factory()->create(['name' => 'Alice']);
    $otherUser = User::factory()->create(['name' => 'Bob']);

    activity()
        ->causedBy($user)
        ->performedOn($user)
        ->event('created')
        ->log('Alice created account');

    activity()
        ->causedBy($otherUser)
        ->performedOn($otherUser)
        ->event('deleted')
        ->log('Bob deleted account');

    $response = $this->actingAs($user)->get(route('audit-logs.index', [
        'search' => 'Alice',
        'filter' => [
            'event' => 'created',
            'causer_id' => $user->id,
        ],
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page): Assert => $page
        ->component('audit-logs/Index')
        ->has('logs.data', 1)
        ->where('logs.data.0.description', 'Alice created account'));
});
