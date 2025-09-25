<?php

namespace App\Http\Resources\Api\V1\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\Api\V1\SalesRepresentative\{
    SalesRepresentativeResource
};
use App\Http\Resources\Api\V1\Customer\{
    CustomerResource
};
use App\Http\Resources\Api\V1\Order\{
    OrderItemCollectionResource
};

use App\Http\Resources\Api\V1\Invoice\{
    InvoiceResource
};


use Carbon\Carbon;

class OrderResource extends JsonResource
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
            "orderId" => $this->order_id,
            "email" => $this->email,
            "mobileNumber" => $this->mobile_number,
            "subtotal" => (float) $this->subtotal,
            "total" => (float) $this->total,
            "status" => $this->status,
            "paymentStatus" => $this->payment_status,
            "invoiceStatus" => $this->invoice_status,
            "invoiceGenerated" => $this->invoice_status === 'generated' ? true : false,
            'invoice'  => $this->invoice ? new InvoiceResource($this->invoice) : null,
            "customer" => new CustomerResource($this->customer),
            "salesRepresentative" => new SalesRepresentativeResource($this->salesRep),
            "items" => OrderItemCollectionResource::collection($this->items),
            'date' => $this->created_at
                ? Carbon::parse($this->created_at)->format('d M, Y')
                : null,
            'dueAmount' => $this->invoice
            ? max(0, (float) $this->invoice->total - (float) $this->invoice->payments->sum('amount_paid'))
            : null,
        ];
    }
}
