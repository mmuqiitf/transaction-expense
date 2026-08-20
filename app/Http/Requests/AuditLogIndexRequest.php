<?php

namespace App\Http\Requests;

class AuditLogIndexRequest extends TableRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->tableRules(['created_at', 'id', 'event', 'log_name']),
            'filter.event' => ['sometimes', 'nullable', 'string', 'max:50'],
            'filter.subject_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'filter.causer_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'filter.from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'filter.to' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:filter.from'],
        ];
    }
}
