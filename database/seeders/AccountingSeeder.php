<?php

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AccountingSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo Account',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $categories = [
            'Salary' => CategoryType::Income,
            'Other Income' => CategoryType::Income,
            'Family Expense' => CategoryType::Expense,
            'Transport Expense' => CategoryType::Expense,
            'Meal Expense' => CategoryType::Expense,
        ];

        foreach ($categories as $name => $type) {
            Category::query()->updateOrCreate(['name' => $name], ['type' => $type]);
        }

        $categoryIds = Category::query()->pluck('id', 'name');

        $accounts = [
            '401' => ['name' => 'Gaji Karyawan', 'category_id' => $categoryIds['Salary']],
            '402' => ['name' => 'Gaji Ketua MPR', 'category_id' => $categoryIds['Salary']],
            '403' => ['name' => 'Profit Trading', 'category_id' => $categoryIds['Other Income']],
            '601' => ['name' => 'Biaya Sekolah', 'category_id' => $categoryIds['Family Expense']],
            '602' => ['name' => 'Bensin', 'category_id' => $categoryIds['Transport Expense']],
            '603' => ['name' => 'Parkir', 'category_id' => $categoryIds['Transport Expense']],
            '604' => ['name' => 'Makan Siang', 'category_id' => $categoryIds['Meal Expense']],
            '605' => ['name' => 'Makanan Pokok Bulanan', 'category_id' => $categoryIds['Meal Expense']],
        ];

        foreach ($accounts as $code => $account) {
            ChartOfAccount::query()->updateOrCreate(['code' => $code], $account);
        }

        $accountIds = ChartOfAccount::query()->pluck('id', 'code');

        $transactions = [
            ['date' => '2022-01-01', 'code' => '401', 'amount' => 5_000_000, 'description' => 'Gaji di perusahaan A'],
            ['date' => '2022-01-02', 'code' => '402', 'amount' => 7_000_000, 'description' => 'Gaji ketum'],
            ['date' => '2022-01-10', 'code' => '602', 'amount' => 25_000, 'description' => 'Bensin anak'],
            ['date' => '2022-01-15', 'code' => '403', 'amount' => 5_500_000, 'description' => 'Profit trading Januari'],
            ['date' => '2022-01-16', 'code' => '601', 'amount' => 500_000, 'description' => 'Biaya sekolah Januari'],
            ['date' => '2022-01-17', 'code' => '603', 'amount' => 175_000, 'description' => 'Parkir Januari'],
            ['date' => '2022-01-18', 'code' => '604', 'amount' => 150_000, 'description' => 'Makan siang Januari'],
            ['date' => '2022-02-01', 'code' => '401', 'amount' => 12_000_000, 'description' => 'Gaji Februari'],
            ['date' => '2022-02-15', 'code' => '403', 'amount' => 6_000_000, 'description' => 'Profit trading Februari'],
            ['date' => '2022-02-16', 'code' => '601', 'amount' => 3_500_000, 'description' => 'Biaya sekolah Februari'],
            ['date' => '2022-02-17', 'code' => '603', 'amount' => 250_000, 'description' => 'Parkir Februari'],
            ['date' => '2022-02-18', 'code' => '604', 'amount' => 300_000, 'description' => 'Makan siang Februari'],
            ['date' => '2022-03-01', 'code' => '401', 'amount' => 12_000_000, 'description' => 'Gaji Maret'],
            ['date' => '2022-03-15', 'code' => '403', 'amount' => 3_500_000, 'description' => 'Profit trading Maret'],
            ['date' => '2022-03-16', 'code' => '601', 'amount' => 4_500_000, 'description' => 'Biaya sekolah Maret'],
            ['date' => '2022-03-17', 'code' => '603', 'amount' => 225_000, 'description' => 'Parkir Maret'],
            ['date' => '2022-03-18', 'code' => '604', 'amount' => 175_000, 'description' => 'Makan siang Maret'],
        ];

        foreach (range(2023, 2026) as $year) {
            $transactions = [...$transactions, ...$this->transactionsForYear($year)];
        }

        foreach ($transactions as $transaction) {
            Transaction::query()->updateOrCreate(
                [
                    'transaction_date' => Carbon::parse($transaction['date']),
                    'chart_of_account_id' => $accountIds[$transaction['code']],
                    'description' => $transaction['description'],
                ],
                ['amount' => $transaction['amount']],
            );
        }
    }

    /**
     * @return list<array{date: string, code: string, amount: int, description: string}>
     */
    private function transactionsForYear(int $year): array
    {
        $transactions = [];
        $yearOffset = $year - 2022;

        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create($year, $month, 1)->format('F');
            $salaryCode = $month % 2 === 0 ? '402' : '401';
            $transportCode = $month % 2 === 0 ? '603' : '602';
            $mealCode = $month % 2 === 0 ? '605' : '604';

            $transactions[] = [
                'date' => Carbon::create($year, $month, 1)->format('Y-m-d'),
                'code' => $salaryCode,
                'amount' => 12_000_000 + ($yearOffset * 750_000) + ($month * 100_000),
                'description' => "Gaji {$monthName} {$year}",
            ];
            $transactions[] = [
                'date' => Carbon::create($year, $month, 10)->format('Y-m-d'),
                'code' => '403',
                'amount' => 3_000_000 + ($yearOffset * 250_000) + ($month * 150_000),
                'description' => "Profit trading {$monthName} {$year}",
            ];
            $transactions[] = [
                'date' => Carbon::create($year, $month, 15)->format('Y-m-d'),
                'code' => '601',
                'amount' => 500_000 + ($yearOffset * 50_000) + (($month % 3) * 75_000),
                'description' => "Biaya sekolah {$monthName} {$year}",
            ];
            $transactions[] = [
                'date' => Carbon::create($year, $month, 20)->format('Y-m-d'),
                'code' => $transportCode,
                'amount' => 200_000 + ($yearOffset * 25_000) + (($month % 4) * 25_000),
                'description' => "Transportasi {$monthName} {$year}",
            ];
            $transactions[] = [
                'date' => Carbon::create($year, $month, 25)->format('Y-m-d'),
                'code' => $mealCode,
                'amount' => 150_000 + ($yearOffset * 20_000) + (($month % 3) * 25_000),
                'description' => "Makan {$monthName} {$year}",
            ];
        }

        return $transactions;
    }
}
