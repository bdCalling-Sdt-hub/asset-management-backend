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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('inspection_sheet_id')->nullable()->constrained('inspection_sheets')->cascadeOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->cascadeOnDelete();
            $table->foreignId('maintainance_id')->nullable()->constrained('maintainances')->cascadeOnDelete();
            $table->enum('report_type', ['job_card', 'asset', 'inspection','ticket']);
            $table->longText('report_for')->nullable();
            $table->longText('report_details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
