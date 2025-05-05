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
        Schema::create('job_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete()->comment('support agent id');
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('inspection_sheet_id')->nullable()->constrained('inspection_sheets')->cascadeOnDelete();
            $table->string('job_card_type')->default('New Cards')->comment('New Cards, Open Cards, Past Cards');;
            $table->longText('support_agent_comment')->nullable();
            $table->longText('technician_comment')->nullable();
            $table->longText('location_employee_signature')->nullable();
            $table->string('job_status')->default('New');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_cards');
    }
};
