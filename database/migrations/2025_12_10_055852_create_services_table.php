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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('yoga_type', ['basic', 'intermediate', 'advanced']);
            $table->json('benefits');
            $table->decimal('price', 8, 2);
            $table->integer('capacity');
            $table->enum('currency', ['USD', 'NRS', 'GBP', 'INR'])->default('NRS');
            $table->json('class_schedule');
            $table->foreignId('instructor_id')->constrained('instructors')->onDelete('cascade');
            $table->string('image')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
