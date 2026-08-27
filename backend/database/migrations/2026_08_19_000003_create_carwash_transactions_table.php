<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carwash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_entry_id')->unique()->constrained('vehicle_entries')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('parking_locations')->restrictOnDelete();
            $table->foreignId('wash_service_id')->nullable()->constrained('wash_services')->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('service_name', 100);
            $table->unsignedInteger('price');
            $table->string('payment_status', 30)->default('paid');
            $table->text('notes')->nullable();
            $table->timestamp('transacted_at')->index();
            $table->timestamps();
            $table->index(['location_id', 'transacted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carwash_transactions');
    }
};
