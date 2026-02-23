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
            $table->foreignId('session_id')->constrained('location_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('point_id')->unique();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('accuracy', 6, 2)->nullable();
            $table->decimal('altitude', 8, 2)->nullable();
            $table->decimal('speed', 6, 2)->nullable();
            $table->decimal('heading', 5, 2)->nullable();
            $table->integer('battery_level')->nullable();
            $table->string('network_type')->nullable();
            $table->timestamp('timestamp');
            $table->timestamps();

            $table->index(['user_id', 'timestamp']);
            $table->index(['session_id', 'timestamp']);
            $table->index('point_id');
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
