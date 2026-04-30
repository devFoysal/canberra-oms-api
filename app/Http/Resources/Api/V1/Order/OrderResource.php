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

use App\Http\Resources\Api\V1\Shipping\{
    ShippingResource
};

use App\Http\Resources\Api\V1\Invoice\{
    InvoiceResource
};

use App\Http\Resources\Api\V1\Payment\{
    PaymentResource
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

        $discountAmount = $this->discounts->sum(function($discount) {
            if ($discount->type === 'percentage') {
                return ((float) $this->total * (float) $discount->value) / 100;
            }
            return (float) $discount->value;
        });

        $totalAfterDiscount = round(max(0, $this->total - $discountAmount + $this->tax), 2);

        return [
            "id" => $this->id,
            "orderId" => $this->order_id,
            "email" => $this->email,
            "mobileNumber" => $this->mobile_number,
            "tax" => (float) $this->tax,
            "subtotal" => round((float) $this->subtotal,2),
            // "total" => round((float) $this->total,2),
            "discountAmount" => $discountAmount,
            "total" => $totalAfterDiscount,
            "status" => status_label($this->status),
            "paymentStatus" => $this->payment_status,
            "invoiceStatus" => $this->invoice_status,
            "invoiceGenerated" => $this->invoice_status === 'generated' ? true : false,
            'invoice'  => $this->invoice ? new InvoiceResource($this->invoice) : null,
            "customer" => new CustomerResource($this->customer),
            "salesRepresentative" => new SalesRepresentativeResource($this->salesRep),
            "items" => OrderItemCollectionResource::collection($this->items),
            'date' => $this->created_at
                ? Carbon::parse($this->created_at)->format('d M, Y h:i A')
                : null,
            'paidAmount' => $this->invoice
                ? round(max(0, (float) $this->invoice->payments->sum('amount_paid') - (float) $discountAmount),2)
                : 0,
            // 'dueAmount' => $this->invoice
            // ? round(max(0, (float) $this->invoice->total - (float) $this->invoice->payments->sum('amount_paid')),2)
            // : null,

            'dueAmount' => $this->invoice
            ? round(max(0, (float) $totalAfterDiscount - (float) $this->invoice->payments->sum('amount_paid')),2)
            : null,
            'payments' => $this->invoice
            ? PaymentResource::collection($this->invoice->payments)
            : [],
            'shipping' => new ShippingResource($this->shipping),
        ];
    }
}
