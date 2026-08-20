<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Transaction */
class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_date' => $this->transaction_date->toDateString(),
            'amount' => (int) $this->amount,
            'description' => $this->description,
            'chart_of_account' => ChartOfAccountResource::make($this->whenLoaded('chartOfAccount')),
        ];
    }
}
