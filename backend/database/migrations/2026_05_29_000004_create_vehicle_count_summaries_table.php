<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_count_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('parking_locations')->onDelete('restrict');
            $table->date('summary_date')->index();
            $table->integer('total_vehicle')->default(0);
            $table->foreignId('generated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();
            $table->unique(['location_id', 'summary_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_count_summaries');
    }
};
