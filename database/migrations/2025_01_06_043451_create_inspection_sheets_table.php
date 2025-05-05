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
        Schema::create('inspection_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->onDelete('cascade');
            $table->foreignId('support_agent_id')->nullable()->constrained('users')->onDelete('cascade')->comment('support agent id');
            $table->foreignId('technician_id')->nullable()->constrained('users')->onDelete('cascade')->comment('technicaian id');
            $table->string('inspection_sheet_type')->default('New Sheets')->comment('New Sheets, Open Sheets, Past Sheets');
            $table->longText('support_agent_comment')->nullable();
            $table->longText('technician_comment')->nullable();
            $table->string('location_employee_signature')->nullable();
            $table->json('image')->nullable();
            $table->json('video')->nullable();
            $table->string('status')->default('New');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_sheets');
    }
};
