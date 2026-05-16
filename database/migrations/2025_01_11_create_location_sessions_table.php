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
        Schema::create('location_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_rep_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->unsignedSmallInteger('total_active_minutes')->default(0);
            $table->unsignedSmallInteger('total_inactive_minutes')->default(0);
            $table->dateTime('last_seen')->nullable();
            $table->boolean('is_online')->default(false);
            $table->unsignedTinyInteger('battery_level')->nullable();
            $table->boolean('battery_charging')->nullable();
            $table->json('activities')->nullable();   // [{area, arrived_at, left_at, duration_minutes}]
            $table->timestamps();

            $table->unique(['sales_rep_id', 'date']); // প্রতিদিন একটিই session
            $table->index('date');
            $table->index(['sales_rep_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_sessions');
    }
};
