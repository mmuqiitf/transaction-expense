<?php

namespace App\Http\Requests;

use App\Enums\CategoryType;
use Illuminate\Validation\Rule;

class TransactionIndexRequest extends TableRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->tableRules(['transaction_date', 'amount', 'created_at']),
            'filter.from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'filter.to' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:filter.from'],
            'filter.type' => ['sometimes', 'nullable', Rule::enum(CategoryType::class)],
            'filter.category_id' => ['sometimes', 'nullable', 'integer', Rule::exists('categories', 'id')],
            'filter.chart_of_account_id' => ['sometimes', 'nullable', 'integer', Rule::exists('chart_of_accounts', 'id')],
        ];
    }
}
