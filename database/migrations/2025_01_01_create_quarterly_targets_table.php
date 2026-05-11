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
        Schema::create('quarterly_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_rep_id')->constrained('users')->cascadeOnDelete();
            $table->enum('target_type', ['sales', 'outlet_visit'])->default('sales');
            $table->date('quarter_start_date');
            $table->date('quarter_end_date');
            $table->decimal('quarterly_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['sales_rep_id', 'target_type']);
            $table->index('quarter_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quarterly_targets');
    }
};
