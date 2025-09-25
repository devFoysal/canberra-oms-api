<?php

namespace App\Http\Resources\Api\V1\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use Carbon\Carbon;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            'transactionNumber' => $this->transaction_number,
            'amount' => (float) $this->amount_paid,
            'method' => $this->method,
            'status' => $this->status,
            'date' => $this->payment_date
                ? Carbon::parse($this->payment_date)->format('d M, Y')
                : null,
            'description' => $this->description,
        ];
    }
}
