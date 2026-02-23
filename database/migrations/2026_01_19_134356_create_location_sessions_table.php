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
            $table->string('session_id')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_id')->nullable();
            $table->decimal('start_latitude', 10, 8);
            $table->decimal('start_longitude', 11, 8);
            $table->decimal('end_latitude', 10, 8)->nullable();
            $table->decimal('end_longitude', 11, 8)->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->decimal('total_distance', 10, 2)->default(0);
            $table->decimal('max_speed', 6, 2)->default(0);
            $table->decimal('avg_speed', 6, 2)->default(0);
            $table->integer('duration')->default(0);
            $table->integer('points_count')->default(0);
            $table->json('device_info')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('session_id');
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
