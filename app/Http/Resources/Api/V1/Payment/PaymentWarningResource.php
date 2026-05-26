<?php

namespace App\Http\Resources\Api\V1\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentWarningResource extends JsonResource
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

            'warningType' => $this->warning_type,
            'isResolved' => $this->is_resolved,

            'orderId' => $this->order_id,

            'dueAmount' => $this->due_amount,
            'paidAmount' => $this->paid_amount,
            'orderTotal' => $this->order_total,
            'daysOverdue' => $this->days_overdue,

            'note' => $this->note,
            'noteAddedAt' => $this->note_added_at,

            'resolvedAt' => $this->resolved_at,
            'resolvedBy' => $this->resolved_by,

            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,

            // relations
            'noteAddedBy' => $this->whenLoaded('noteAddedBy', function () {
                return [
                    'id' => $this->noteAddedBy?->id,
                    'name' => $this->noteAddedBy?->full_name,
                ];
            }),

            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer?->id,
                    'name' => $this->customer?->name,
                    'mobileNumber' => $this->customer?->mobile_number,
                    'address' => $this->customer?->address,
                ];
            }),

            'sr' => $this->whenLoaded('sr', function () {
                return [
                    'id' => $this->sr?->id,
                    'name' => $this->sr?->full_name,
                    'mobileNumber' => $this->sr?->mobile_number,
                ];
            }),

            'order' => $this->whenLoaded('order', function () {
                return [
                    'id' => $this->order?->id,
                    'orderId' => $this->order?->order_id,
                    'status' => $this->order?->status,
                    'paymentStatus' => $this->order?->payment_status,
                    'total' => $this->order?->total,
                    'createdAt' => $this->order?->created_at,
                    'updatedAt' => $this->order?->updated_at,
                    'items' => $this->order?->items,
                ];
            }),
        ];
    }
}
