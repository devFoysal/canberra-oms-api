<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            // Invoice number (INV-<timestamp> or sequential)
            $table->string('invoice_number')->unique();

            // Link to order
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            // Billing details
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            // Dates
            $table->timestamp('issue_date')->useCurrent();
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Status tracking
            $table->enum('status', ['unpaid', 'partial', 'paid', 'overdue', 'cancelled'])->default('unpaid');

            // Optional references
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('modified_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
