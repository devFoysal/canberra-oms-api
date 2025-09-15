<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\{
    ResponseHelper,
};
use App\Models\{
    Order,
    Invoice
};
use App\Http\Requests\Api\V1\Invoice\{
    CreateInvoiceRequest,
};
use App\Http\Resources\Api\V1\Order\{
    OrderResource,
};

use DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index()
    {
        //
    }

    public function store(CreateInvoiceRequest $request)
    {

        $data = $request->validated();

        $order = Order::find($data['orderId']);
        if (!$order) return ResponseHelper::error('Order not found', 404);

        DB::beginTransaction();

        try {
            // Prepare order data
            $invoiceData = [
                'order_id' => $order->id,
                'subtotal' => $order->subtotal,
                'tax' => $order->tax,
                'total' => $order->total,
                'due_date' => Carbon::now(),
                'customer_id' => $order->customer_id,
                'created_by_id' => auth()->user()->id,
            ];

            // Create invoice
            Invoice::create($invoiceData);

            // Update order invoice status
            $order->invoice_status = 'generated';
            $order->update();

            DB::commit();

            $order->load(['customer', 'salesRep', 'items.product:id,thumbnail', 'invoice']);

            return ResponseHelper::success(new OrderResource($order), 'Invoice generated', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }

    }

    public function show($id)
    {

    }

    public function update(EditOrderRequest $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }

    public function downloadInvoice($id)
    {
        $invoice = Invoice::find($id);
        if (!$invoice) return ResponseHelper::error('Invoice not found', 404);

        $order = Order::find($invoice->order_id);
        if (!$order) return ResponseHelper::error('Order not found', 404);

        $order->load(['customer', 'salesRep', 'invoice', 'items']);

        // return  ResponseHelper::success($order, 'Invoice not found', 404);
        $pdf = Pdf::loadView('invoices.show', compact('order'))
        // ->setPaper('a4', 'portrait')
        ->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isPhpEnabled' => true,
        ]);
        // return view('invoices.show', compact('order'));

        return $pdf->download("Invoice_{$invoice->invoice_number}_{$order->id}.pdf");
    }
}
