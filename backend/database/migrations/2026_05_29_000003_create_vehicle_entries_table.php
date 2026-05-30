<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('parking_locations')->onDelete('restrict');
            $table->foreignId('sensor_id')->constrained('ultrasonic_sensors')->onDelete('restrict');
            $table->timestamp('entry_time')->index();
            $table->integer('vehicle_count')->default(1);
            $table->decimal('detection_confidence', 5, 2)->nullable();
            $table->decimal('raw_distance', 8, 2)->nullable();
            $table->string('device_event_id', 150)->unique()->nullable();
            $table->timestamps();
            $table->index(['location_id', 'entry_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_entries');
    }
};
