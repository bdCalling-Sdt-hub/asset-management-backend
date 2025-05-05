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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('users')->cascadeOnDelete()->comment('super_admin , organizaton , third_party');
            $table->string('product_id')->nullable();
            $table->string('brand')->nullable();
            $table->string('range')->nullable();
            $table->string('product')->nullable();
            $table->string('qr_code')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('external_serial_number')->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->date('installation_date')->nullable();
            $table->date('warranty_end_date')->nullable();
            $table->float('unit_price')->nullable();
            $table->float('current_spend')->nullable();
            $table->float('max_spend')->nullable();
            $table->boolean('fitness_product')->nullable();
            $table->boolean('has_odometer')->nullable();
            $table->string('location')->nullable();
            $table->float('residual_price')->nullable();
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
