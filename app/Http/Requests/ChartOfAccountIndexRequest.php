<?php

namespace App\Http\Requests;

use App\Enums\CategoryType;
use Illuminate\Validation\Rule;

class ChartOfAccountIndexRequest extends TableRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->tableRules(['code', 'name', 'created_at']),
            'filter.category_id' => ['sometimes', 'nullable', 'integer', Rule::exists('categories', 'id')],
            'filter.type' => ['sometimes', 'nullable', Rule::enum(CategoryType::class)],
        ];
    }
}
