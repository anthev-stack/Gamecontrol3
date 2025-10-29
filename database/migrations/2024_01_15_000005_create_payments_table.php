<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uuid', 36)->unique();
            $table->string('transaction_id')->unique();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('order_id')->nullable();
            $table->unsignedInteger('invoice_id')->nullable();
            
            // Payment details
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->string('currency', 3)->default('USD');
            $table->enum('payment_method', ['stripe', 'paypal', 'bank_transfer', 'cash', 'other'])->default('other');
            
            // Payment gateway details
            $table->string('gateway')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->text('gateway_response')->nullable(); // JSON response from gateway
            
            // Status
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending');
            
            // Billing information
            $table->string('billing_name')->nullable();
            $table->string('billing_email')->nullable();
            
            // Additional metadata
            $table->text('notes')->nullable();
            $table->text('metadata')->nullable(); // JSON for additional data
            
            $table->timestamps();
            $table->timestamp('processed_at')->nullable();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}


