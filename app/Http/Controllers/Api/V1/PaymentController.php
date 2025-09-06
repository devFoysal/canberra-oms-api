<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Order;
use App\Helpers\{
    ResponseHelper,
    PaymentHelper
};
use App\Http\Requests\Api\V1\Payment\{
    CreatePaymentRequest,
    EditPaymentRequest
};

use App\Http\Resources\Api\V1\Order\{
    OrderResource,
};

use DB;

class PaymentController extends Controller
{
    public function index()
    {
        // $payments = Payment::get();
        return ResponseHelper::success(null, 'Payments retrieved successfully');
    }

    public function store(CreatePaymentRequest $request)
    {

        $data = $request->validated();

        $order = Order::find($data['orderId']);

        if (!$order) return ResponseHelper::error('Order not found', 404);

        DB::beginTransaction();

        try {
            // Prepare payment data
            $paymentData = [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'method' => $data['method'],
                'amount' => $data['amount'],
                'details' => $data['details'],
            ];

            // Create payment
            $payment = Payment::create($paymentData);

            if($payment){
                $order->payment_status = 'paid';
                $order->update();
            }

            DB::commit();

            $order->load(['customer', 'salesRep', 'items.product:id,thumbnail']);

            return ResponseHelper::success(new OrderResource($order), 'Payment successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }

    }

    public function show($id)
    {
        // $order = Payment::with(['items', 'customer', 'items.product:id,thumbnail'])->find($id);
        // if (!$order) return ResponseHelper::error('Payment not found', 404);
        // return ResponseHelper::success(new PaymentResource($order), 'Payment retrieved successfully');
    }

    public function update(EditPaymentRequest $request, $id)
    {
        // $order = Payment::find($id);

        // if (!$order) return ResponseHelper::error('Payment not found', 404);


        // DB::beginTransaction();

        // try {

        //     $data = $request->validated();

        //     // Prepare order data
        //     $orderData = [
        //         'subtotal' => $data['subtotal'],
        //         'tax' => $data['tax'] ?? 0,
        //         'total' => $data['total'],
        //         'modified_by_id' => auth()->user()->id,
        //     ];

        //     if($data['saveChange'] !== true){
        //         $orderData['status'] = 'confirmed';
        //     }

        //     // Payment update
        //     $order->update($orderData);

        //     // Update order items
        //     foreach ($data['items'] as $itemData) {
        //         $order->items()->updateOrCreate(
        //             ['id' => $itemData['id']],
        //             [
        //                 'price' => $itemData['salePrice'],
        //                 'quantity' => $itemData['quantity'],
        //                 'total' => (float) $itemData['salePrice'] * (int)$itemData['quantity'],
        //             ]
        //         );
        //     }

        //     DB::commit();

        //     $order->load(['customer', 'salesRep', 'items.product:id,thumbnail']);

        //     return ResponseHelper::success(new PaymentResource($order), 'Payment confirmed successfully');

        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     return ResponseHelper::error($e->getMessage(), 500);
        // }
    }

    public function destroy($id)
    {
        // $order = Payment::find($id);
        // if (!$order) return ResponseHelper::error('Payment not found', 404);

        // $order->delete();
        // return ResponseHelper::success('Payment deleted successfully');
    }
}
