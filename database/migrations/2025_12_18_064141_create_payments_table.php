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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            // Customer Information
            $table->string('full_name');
            $table->string('email');
            $table->string('phone_number');
            
            // Invoice/Order Details
            $table->string('invoice_number');
            $table->text('description')->nullable();
            
            // Payment Details
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('aud');
            $table->enum('status', [
                'pending',
                'succeeded',
                'failed',
                'canceled',
            ])->default('pending');
            
            // Stripe Integration
            $table->string('payment_intent_id')->nullable()->unique();
            $table->string('client_secret')->nullable();

            
        
            $table->timestamp('paid_at')->nullable();

            
            $table->timestamps();
            
            // Indexes
            $table->index('email');
            $table->index('invoice_number');
            $table->index('payment_intent_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};