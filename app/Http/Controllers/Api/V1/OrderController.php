<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Invoice;
use App\Models\Product;
use App\Helpers\{
    ResponseHelper,
    OrderHelper
};
use App\Http\Requests\Api\V1\Order\{
    ProductOrderRequest,
    EditOrderRequest,
    AddMoreProductRequest,
    RemoveOrderItemRequest
};

use App\Http\Resources\Api\V1\Order\{
    OrderCollectionResource,
    OrderResource,
};

use Carbon\Carbon;
use DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['customer', 'salesRep', 'items.product:id,thumbnail'])->orderBy('id', 'desc')->get();
        return ResponseHelper::success(OrderCollectionResource::collection($orders), 'Orders retrieved successfully');
    }

    public function store(ProductOrderRequest $request)
    {

        $data = $request->validated();

        DB::beginTransaction();

        try {
            // Prepare order data
            $orderData = [
                'order_id' => OrderHelper::generateOrderId(),
                'customer_id' => $data['customer'] ?? null,
                'sales_rep_id' => auth()->user()->id,
                'subtotal' => $data['subtotal'],
                'tax' => $data['tax'] ?? 0,
                'total' => $data['total']
            ];

            // Create order
            $order = Order::create($orderData);

            // Create order items
            foreach ($data['items'] as $item) {
                $itemData = [
                    'order_id' => $order->id,
                    'product_id' => (int) $item['id'],
                    'product_name' => (string) $item['name'],
                    'price' => (float) $item['salePrice'],
                    'quantity' => (int) $item['quantity'],
                    'total' => (float) $item['salePrice'] * (int) $item['quantity'],
                ];

                OrderItem::create($itemData);
            }

            DB::commit();

            $order->load(['customer', 'salesRep', 'items.product:id,thumbnail']);

            return ResponseHelper::success(new OrderResource($order), 'Order created successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }

    }

    public function show($id)
    {
        $order = Order::with(['items', 'customer', 'items.product:id,thumbnail'])->find($id);
        if (!$order) return ResponseHelper::error('Order not found', 404);
        return ResponseHelper::success(new OrderResource($order), 'Order retrieved successfully');
    }

    public function update(EditOrderRequest $request, $id)
    {
        $order = Order::find($id);

        if (!$order) return ResponseHelper::error('Order not found', 404);


        DB::beginTransaction();

        try {

            $data = $request->validated();

            // Prepare order data
            $orderData = [
                'subtotal' => $data['subtotal'],
                'tax' => $data['tax'] ?? 0,
                'total' => $data['total'],
                'modified_by_id' => auth()->user()->id,
            ];

            if($data['saveChange'] !== true){
                $orderData['status'] = 'confirmed';
            }

            // Order update
            $order->update($orderData);

            // Update order items
            foreach ($data['items'] as $itemData) {
                $order->items()->updateOrCreate(
                    ['id' => $itemData['id']],
                    [
                        'price' => $itemData['salePrice'],
                        'quantity' => $itemData['quantity'],
                        'total' => (float) $itemData['salePrice'] * (int)$itemData['quantity'],
                    ]
                );
            }

            /*
                Generate Invoice
            */
            // Prepare invoice data
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

            return ResponseHelper::success(new OrderResource($order), 'Order confirmed');

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }



    public function destroy($id)
    {
        $order = Order::find($id);
        if (!$order) return ResponseHelper::error('Order not found', 404);

        $order->delete();
        return ResponseHelper::success('Order deleted successfully');
    }

    // Order item
    public function addMoreOrderItem(AddMoreProductRequest $request, $id)
    {
        $order = Order::find($id);

        if (!$order) return ResponseHelper::error('Order not found', 404);

        DB::beginTransaction();

        try {

            $data = $request->validated();

            $product = Product::find($data['productId']);

            if (!$product) return ResponseHelper::error('Product not found', 404);

            $orderItem = [
                'product_id' => (int) $product->id,
                'product_name' => (string) $product->name,
                'price' => $product->sale_price,
                'quantity' => 1,
                'total' => (float) $product->sale_price
            ];

            // New order item add
            $order->items()->create($orderItem);

            DB::commit();

            $order->load(['customer', 'salesRep', 'items.product:id,thumbnail']);

            return ResponseHelper::success(new OrderResource($order), 'Product added to cart');

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function removeOrderItem(RemoveOrderItemRequest $request, $id)
    {

        $order = Order::find($id);
        if (!$order) return ResponseHelper::error('Order not found', 404);

        $data = $request->validated();

        $orderItem = OrderItem::find($data['itemId']);
        if (!$orderItem) return ResponseHelper::error('Item not found', 404);

        if ($orderItem->order_id !== $order->id) return ResponseHelper::error('Item not found', 404);

        try {
            $orderItem->delete();

             $order->load(['customer', 'salesRep', 'items.product:id,thumbnail']);
            return ResponseHelper::success(new OrderResource($order), 'Product removed from cart');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

}
