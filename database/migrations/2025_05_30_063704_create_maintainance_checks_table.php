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
        Schema::create('maintainance_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintainance_id')->constrained('maintainances')->onDelete('cascade');
            $table->enum('maintainance_type',['weekly','monthly','yearly'])->default('weekly');
            $table->string('week')->nullable();
            $table->string('month')->nullable();
            $table->year('year')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintainance_checks');
    }
};
