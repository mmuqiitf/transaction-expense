<?php

use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\Activitylog\Models\Activity;

test('guests cannot import transactions', function () {
    $this->post(route('transactions.import'), [])->assertRedirect(route('login'));
});

test('transaction import requires a valid file', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('transactions.import'), [])
        ->assertSessionHasErrors('file');

    $invalidFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->actingAs($user)
        ->post(route('transactions.import'), ['file' => $invalidFile])
        ->assertSessionHasErrors('file');
});

test('authenticated users can import transactions via CSV with amount', function () {
    $user = User::factory()->create();
    $category = Category::factory()->income()->create(['name' => 'Salary']);
    $account = ChartOfAccount::factory()->for($category)->create([
        'code' => '401',
        'name' => 'Gaji Karyawan',
    ]);

    $csvContent = implode("\n", [
        'date,coa_code,amount,description',
        '2022-01-01,401,5000000,Gaji Januari',
        '2022-02-01,401,5000000,Gaji Februari',
    ]);

    $file = UploadedFile::fake()->createWithContent('transactions.csv', $csvContent);

    $response = $this->actingAs($user)->post(route('transactions.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('transactions.index'));
    $response->assertSessionHasNoErrors();

    expect(Transaction::count())->toBe(2);

    $firstTransaction = Transaction::query()->oldest('id')->first();
    expect($firstTransaction)->not->toBeNull()
        ->and($firstTransaction->chart_of_account_id)->toBe($account->id)
        ->and($firstTransaction->transaction_date->toDateString())->toBe('2022-01-01')
        ->and($firstTransaction->amount)->toBe(5000000)
        ->and($firstTransaction->description)->toBe('Gaji Januari');

    // Check model-level activity logs were created for each transaction
    $transactionActivity = Activity::query()
        ->where('subject_type', Transaction::class)
        ->where('subject_id', $firstTransaction->id)
        ->where('event', 'created')
        ->first();
    expect($transactionActivity)->not->toBeNull();

    // Check the manual import summary activity log
    expect(Activity::where('description', 'like', '%Imported 2 transactions%')->exists())->toBeTrue();
});

test('authenticated users can import transactions via CSV with debit and credit columns', function () {
    $user = User::factory()->create();
    $salaryCategory = Category::factory()->income()->create(['name' => 'Salary']);
    $expenseCategory = Category::factory()->expense()->create(['name' => 'Transport']);

    $salaryAccount = ChartOfAccount::factory()->for($salaryCategory)->create(['code' => '401']);
    $transportAccount = ChartOfAccount::factory()->for($expenseCategory)->create(['code' => '602']);

    $csvContent = implode("\n", [
        'date,coa_code,debit,credit,description',
        '2022-01-01,401,0,"5,000,000",Gaji di perusahaan A',
        '2022-01-10,602,"25,000",0,Bensin anak',
    ]);

    $file = UploadedFile::fake()->createWithContent('sample_ledger.csv', $csvContent);

    $response = $this->actingAs($user)->post(route('transactions.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('transactions.index'));

    $salaryTransaction = Transaction::query()
        ->where('chart_of_account_id', $salaryAccount->id)
        ->first();
    expect($salaryTransaction)->not->toBeNull()
        ->and($salaryTransaction->transaction_date->toDateString())->toBe('2022-01-01')
        ->and($salaryTransaction->amount)->toBe(5000000);

    $transportTransaction = Transaction::query()
        ->where('chart_of_account_id', $transportAccount->id)
        ->first();
    expect($transportTransaction)->not->toBeNull()
        ->and($transportTransaction->transaction_date->toDateString())->toBe('2022-01-10')
        ->and($transportTransaction->amount)->toBe(25000);
});

test('transaction import validates nonexistent coa code', function () {
    $user = User::factory()->create();

    $csvContent = implode("\n", [
        'date,coa_code,amount,description',
        '2022-01-01,999,5000000,Invalid account',
    ]);

    $file = UploadedFile::fake()->createWithContent('invalid.csv', $csvContent);

    $response = $this->actingAs($user)->post(route('transactions.import'), [
        'file' => $file,
    ]);

    $response->assertSessionHasErrors('file');
    expect(Transaction::count())->toBe(0);
});

test('transaction import validates invalid date format', function () {
    $user = User::factory()->create();
    $category = Category::factory()->income()->create();
    ChartOfAccount::factory()->for($category)->create(['code' => '401']);

    $csvContent = implode("\n", [
        'date,coa_code,amount,description',
        'invalid-date,401,5000000,Test',
    ]);

    $file = UploadedFile::fake()->createWithContent('invalid_date.csv', $csvContent);

    $response = $this->actingAs($user)->post(route('transactions.import'), [
        'file' => $file,
    ]);

    $response->assertSessionHasErrors('file');
    expect(Transaction::count())->toBe(0);
});

test('authenticated users can download sample import file', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('transactions.import.sample'));

    $response->assertOk();
    $response->assertDownload('sample-transactions-import.csv');
});
