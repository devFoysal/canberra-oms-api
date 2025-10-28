<?php

namespace App\Http\Resources\Api\V1\Invoice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use Carbon\Carbon;

class InvoiceResource extends JsonResource
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
            "id" => $this->id,
            "invoiceNumber" => $this->invoice_number,
            'issueDate' => $this->issue_date
                ? Carbon::parse($this->issue_date)->format('d M, Y h:i A')
                : null,
            "status" => $this->status,
        ];
    }
}
