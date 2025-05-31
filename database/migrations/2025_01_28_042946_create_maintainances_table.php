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
        Schema::create('maintainances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->cascadeOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->date('last_maintainance')->nullable();
            $table->date('next_schedule')->nullable();
            $table->enum('status', ['RemindMeLetter', 'ContactToTechnician'])->default('RemindMeLetter');
            $table->enum('reminder_category', ['Weekly', 'Monthly', 'Yearly'])->default('Weekly');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintainances');
    }
};
