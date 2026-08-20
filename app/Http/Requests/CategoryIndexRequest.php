<?php

namespace App\Http\Requests;

use App\Enums\CategoryType;
use Illuminate\Validation\Rule;

class CategoryIndexRequest extends TableRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->tableRules(['name', 'type', 'created_at']),
            'filter.type' => ['sometimes', 'nullable', Rule::enum(CategoryType::class)],
        ];
    }
}
