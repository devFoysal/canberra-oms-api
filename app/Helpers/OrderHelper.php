<?php

namespace App\Helpers;

use App\Models\Order;
use Illuminate\Support\Str;

class OrderHelper
{
    /**
     * Generate a unique order ID
     * Example: ORD-1693723480123
     */
    public static function generateOrderId(): string
    {
        do {
            $orderId = 'ORD-' . now()->timestamp . '-' . Str::upper(Str::random(4));
        } while (Order::where('order_id', $orderId)->exists());

        return $orderId;
    }

    /**
     * Generate a unique invoice number
     * Example: INV-2025-0001
     */
    public static function generateInvoiceNumber(): string
    {
        do {
            $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . rand(1000, 9999);
        } while (Order::where('invoice_number', $invoiceNumber)->exists());

        return $invoiceNumber;
    }
}
