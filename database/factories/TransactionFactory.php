<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chart_of_account_id' => ChartOfAccountFactory::new(),
            'transaction_date' => fake()->dateTimeBetween('2022-01-01', '2026-12-31')->format('Y-m-d'),
            'amount' => fake()->numberBetween(1_000, 10_000_000),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
