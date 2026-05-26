<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// File: database/migrations/2025_09_15_000001_create_payment_warnings_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_warnings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            $table->foreignId('sales_rep_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

            // '15_days' = confirmed + no payment recorded at all
            // '30_days' = partial payment recorded but due still exists
            $table->enum('warning_type', ['15_days', '30_days']);

            // Days overdue at time of generation
            $table->unsignedInteger('days_overdue');

            // Total order amount
            $table->decimal('order_total', 12, 2)->default(0);

            // How much has been paid (0 for 15-day type)
            $table->decimal('paid_amount', 12, 2)->default(0);

            // Remaining due
            $table->decimal('due_amount', 12, 2)->default(0);

            // Admin/SR note after customer conversation
            $table->longText('note')->nullable();

            // Who added the note
            $table->foreignId('note_added_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('note_added_at')->nullable();

            // Whether admin has acted on this warning
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            // One warning per order per type (prevent duplicates)
            $table->unique(['order_id', 'warning_type']);

            $table->index(['warning_type', 'is_resolved']);
            $table->index('customer_id');
            $table->index('sales_rep_id');
            $table->index('days_overdue');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_warnings');
    }
};
