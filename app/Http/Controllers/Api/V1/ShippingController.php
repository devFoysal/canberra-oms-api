<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Shipping;
use App\Http\Requests\Api\V1\Shipping\{
    CreateShippingRequest,
};
use App\Http\Resources\Api\V1\Order\{
    OrderCollectionResource,
    OrderResource
};

use App\Helpers\{
    ResponseHelper
};

use DB;

class ShippingController extends Controller
{
    public function index()
    {
        $orders = Order::whereIn('status',  [
            'confirmed',
            'ready_to_ship',
            'shipped',
            'delivered',
        ])->with(['customer', 'items.product:id,thumbnail'])->orderBy('id', 'desc')->get();
        return ResponseHelper::success(OrderCollectionResource::collection($orders), 'Orders retrieved successfully');
    }

    public function readyToShip($id)
    {
        $order = Order::find($id);

        if (!$order) return ResponseHelper::error('Order not found', 404);
        if ($order->status != 'confirmed') {
            return ResponseHelper::error('Order is not confirmed', 400);
        }

        try {
            $order->status = 'ready_to_ship';
            $order->save();
            return ResponseHelper::success(new OrderResource($order), 'Order status updated to ready to ship successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function store(CreateShippingRequest $request, $id)
    {
        $order = Order::find($id);

        if (!$order) return ResponseHelper::error('Order not found', 404);
        if ($order->status != 'ready_to_ship') {
            return ResponseHelper::error('Order is not ready to ship', 400);
        }

        try {
            Shipping::create([
                'estimated_delivery_date' => $request->date,
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'created_by' => auth()->user()->id,
                'note' => $request->note,
                'status' => 'shipped',
            ]);
            $order->status = 'shipped';
            $order->save();

            return ResponseHelper::success(new OrderResource($order), 'Order shipped successfully');

        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function delivered($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return ResponseHelper::error('Order not found', 404);
        }

        if ($order->status !== 'shipped') {
            return ResponseHelper::error('Order is not shipped yet', 400);
        }

        if (!$order->shipping) {
            return ResponseHelper::error('Shipping record not found for this order', 404);
        }

        DB::beginTransaction();

        try {
            $order->status = 'delivered';
            $order->save();

            $order->shipping->status = 'delivered';
            $order->shipping->delivery_date = now()->toDateString();
            $order->shipping->save();

            DB::commit();

            return ResponseHelper::success(
                new OrderResource($order),
                'Order status updated to delivered successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

}
