<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCreditTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uuid', 36)->unique();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('admin_id')->nullable(); // Admin who gave the credits
            $table->decimal('amount', 10, 2); // Positive for credit, negative for debit
            $table->decimal('balance_after', 10, 2); // Balance after transaction
            $table->enum('type', ['admin_grant', 'purchase', 'refund', 'payment'])->default('payment');
            $table->enum('reason', ['giveaway', 'refund', 'gift', 'purchase', 'payment', 'other'])->nullable();
            $table->string('description')->nullable();
            $table->unsignedInteger('order_id')->nullable(); // Related order if applicable
            $table->text('metadata')->nullable(); // JSON for additional data
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('credit_transactions');
    }
}

