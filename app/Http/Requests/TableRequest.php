<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @param  list<string>  $sortColumns
     * @return array<string, array<int, mixed>>
     */
    protected function tableRules(array $sortColumns): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', Rule::in([15, 25, 50, 100])],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort' => ['sometimes', 'nullable', 'string', Rule::in($sortColumns)],
            'direction' => ['sometimes', 'nullable', Rule::in(['asc', 'desc'])],
            'filter' => ['sometimes', 'array'],
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 15);
    }

    public function searchTerm(): ?string
    {
        $search = $this->validated('search');

        return is_string($search) && trim($search) !== '' ? trim($search) : null;
    }

    public function sortColumn(): ?string
    {
        $sort = $this->validated('sort');

        return is_string($sort) ? $sort : null;
    }

    /** @return 'asc'|'desc' */
    public function sortDirection(): string
    {
        $direction = $this->validated('direction');

        return $direction === 'desc' ? 'desc' : 'asc';
    }

    public function filter(string $key): mixed
    {
        return $this->validated("filter.{$key}");
    }
}
