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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('user');
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->string('ticket_type')->default('New Tickets');
            $table->longText('problem');
            $table->longText('user_comment')->nullable();
            $table->string('ticket_status')->default('New');
            $table->string('cost')->nullable();
            $table->string('order_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
