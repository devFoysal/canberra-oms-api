<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .invoice-wrapper {
            max-width: 900px;
            margin: 50px auto;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            padding: 40px 50px;
        }

        /* Header */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #ececec;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .invoice-header h1 {
            font-size: 32px;
            color: #1f2937;
            margin: 0;
        }

        .invoice-logo {
            width: 120px;
            height: auto;
        }

        /* Sections */
        .invoice-section {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .invoice-section div {
            flex: 1 1 45%;
            margin-bottom: 20px;
        }

        .invoice-section h3 {
            color: #1f2937;
            font-size: 18px;
            margin-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 5px;
        }

        .invoice-section p {
            margin: 4px 0;
            font-size: 15px;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table th,
        table td {
            padding: 15px 12px;
            text-align: left;
        }

        table th {
            background-color: #1f2937;
            color: #fff;
            font-weight: 600;
            border: none;
        }

        table tbody tr {
            border-bottom: 1px solid #e0e0e0;
        }

        table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        .total-row td {
            font-weight: 700;
            background-color: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        /* Footer */
        .invoice-footer {
            text-align: center;
            margin-top: 40px;
            font-size: 14px;
            color: #777;
            border-top: 1px solid #ececec;
            padding-top: 20px;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .invoice-section {
                flex-direction: column;
            }

            .invoice-section div {
                flex: 1 1 100%;
            }

            .invoice-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .invoice-logo {
                margin-top: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-wrapper">
        <!-- Header -->
        <div class="invoice-header">
            <h1>Invoice</h1>
            <img src="https://via.placeholder.com/120x50?text=Logo" alt="Company Logo" class="invoice-logo">
        </div>

        <!-- Order & Invoice Info -->
        <div class="invoice-section">
            <div>
                <h3>Order Information</h3>
                <p><strong>Order ID:</strong> {{ $order->order_id }}</p>
                <p><strong>Customer:</strong> {{ $order->customer->name }}</p>
                <p><strong>Date:</strong> {{ $order->created_at->format('Y-m-d') }}</p>
            </div>
            <div>
                <h3>Invoice Information</h3>
                <p><strong>Invoice Number:</strong> {{ $order->invoice->invoice_number }}</p>
                <p><strong>Status:</strong> {{ $order->invoice->status }}</p>
                <p><strong>Subtotal:</strong> Tk{{ $order->subtotal }}</p>
                <p><strong>Tax:</strong> Tk{{ $order->tax }}</p>
                <p><strong>Total:</strong> Tk{{ $order->total }}</p>
            </div>
        </div>

        <!-- Items Table -->
        <div class="invoice-section">
            <h3>Order Items</h3>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>Tk{{ $item->price }}</td>
                        <td>Tk{{ $item->quantity * $item->price }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="3" class="text-right">Grand Total</td>
                        <td>Tk{{ $order->total }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            Thank you for your business!<br>
            &copy; {{ date('Y') }} Canberra
        </div>
    </div>
</body>

</html>
