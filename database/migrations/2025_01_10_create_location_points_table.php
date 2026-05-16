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
        Schema::create('location_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_rep_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('latitude',  10, 7);
            $table->decimal('longitude', 10, 7);
            $table->float('accuracy')->nullable();    // GPS accuracy in meters
            $table->float('speed')->nullable();       // m/s
            $table->float('heading')->nullable();     // degrees
            $table->unsignedTinyInteger('battery_level')->nullable();   // 0–100
            $table->boolean('battery_charging')->nullable();
            $table->string('area')->nullable();       // reverse geocoded area name
            $table->dateTime('recorded_at');          // client timestamp

            // Server-side created_at used for sync tracking
            $table->timestamps();

            // Index এ pressure কমাতে:
            // - sales_rep_id + recorded_at দিয়েই সব query চলবে
            // - area index টা report query এর জন্য
            $table->index(['sales_rep_id', 'recorded_at']);
            $table->index('recorded_at');
            $table->index('area');

            // Partition hint: production এ date-based partitioning বিবেচনা করো
            // ALTER TABLE location_points PARTITION BY RANGE (YEAR(recorded_at))
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_points');
    }
};
