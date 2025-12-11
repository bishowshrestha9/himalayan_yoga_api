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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('userName');
            $table->string('userEmail');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->string('fromDate');
            $table->string('toDate');
            $table->string('time');
            $table->enum('status', ['confirmed', 'pending', 'cancelled'])->default('pending');
            $table->integer('participants');
            $table->decimal('price', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
