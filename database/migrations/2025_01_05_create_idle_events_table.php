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
        Schema::create('idle_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_rep_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('resolved_time')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->enum('reason_type', [
                'traveling',
                'lunch_prayer',
                'customer_meeting',
                'market_closed',
                'no_response',
                'other',
            ])->nullable();
            $table->text('reason_note')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();

            $table->index(['sales_rep_id', 'is_resolved']);
            $table->index('start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idle_events');
    }
};
