<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfitLossReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'array'],
            'filter.year' => ['sometimes', 'nullable', 'integer', 'min:2022', 'max:2100'],
            'filter.from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'filter.to' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:filter.from'],
            'filter.category_id' => ['sometimes', 'nullable', 'integer', Rule::exists('categories', 'id')],
        ];
    }

    public function year(): ?int
    {
        $value = $this->validated('filter.year');

        return $value === null ? null : (int) $value;
    }

    public function selectedYear(): ?int
    {
        if ($this->year() !== null) {
            return $this->year();
        }

        if ($this->validated('filter.from') || $this->validated('filter.to')) {
            return null;
        }

        return CarbonImmutable::now()->year;
    }

    public function fromDate(): CarbonImmutable
    {
        $year = $this->selectedYear();
        $value = $this->validated('filter.from');

        if ($year !== null) {
            return CarbonImmutable::create($year, 1, 1)->startOfDay();
        }

        return $value
            ? CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay()
            : CarbonImmutable::now()->startOfYear()->startOfDay();
    }

    public function toDate(): CarbonImmutable
    {
        $year = $this->selectedYear();
        $value = $this->validated('filter.to');

        if ($year !== null) {
            return CarbonImmutable::create($year, 12, 31)->endOfDay();
        }

        return $value
            ? CarbonImmutable::createFromFormat('Y-m-d', $value)->endOfDay()
            : CarbonImmutable::now()->endOfYear()->endOfDay();
    }

    public function categoryId(): ?int
    {
        $value = $this->validated('filter.category_id');

        return $value === null ? null : (int) $value;
    }
}
