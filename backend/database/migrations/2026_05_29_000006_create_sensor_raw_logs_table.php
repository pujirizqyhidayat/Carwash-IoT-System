<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_raw_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->constrained('ultrasonic_sensors')->onDelete('restrict');
            $table->decimal('distance_value', 8, 2);
            $table->boolean('is_detected')->default(false);
            $table->json('payload');
            $table->timestamp('received_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_raw_logs');
    }
};
