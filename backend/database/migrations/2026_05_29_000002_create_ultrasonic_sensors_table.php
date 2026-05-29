<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ultrasonic_sensors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('parking_locations')->onDelete('restrict');
            $table->string('sensor_name', 150);
            $table->string('sensor_code', 100)->unique();
            $table->enum('sensor_position', ['entry', 'exit'])->default('entry');
            $table->enum('status', ['active', 'inactive', 'disconnected'])->default('active');
            $table->decimal('threshold_distance', 8, 2)->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->index(['location_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ultrasonic_sensors');
    }
};
