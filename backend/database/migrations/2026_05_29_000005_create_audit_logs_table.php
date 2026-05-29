<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action', 100)->index();
            $table->string('module', 100)->index();
            $table->text('description');
            $table->string('ip_address', 50)->nullable();
            $table->text('user_agent')->nullable();
            $table->enum('status', ['success', 'failed', 'warning'])->default('success');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->index()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
