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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->string('method')->nullable();

            $table->string('payment_date')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('BDT');
            $table->longText('description')->nullable();
            $table->string('ref')->nullable();

            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            // Corrected 'partial'
            $table->enum('status', ['pending', 'processing', 'partial', 'paid', 'failed'])->default('pending');

            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            // Track amount paid (optional but useful for partial payments)
            $table->decimal('amount_paid', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
