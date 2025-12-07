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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->text('message')->nullable();
            $table->string('source')->default('review'); // 'review', 'booking', 'contact', etc.
            $table->string('status')->default('pending'); // 'pending', 'contacted', 'converted', 'rejected'
            $table->json('metadata')->nullable(); // Store additional data like rating, review text, etc.
            $table->timestamps();
            
            $table->index('email');
            $table->index('status');
            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
