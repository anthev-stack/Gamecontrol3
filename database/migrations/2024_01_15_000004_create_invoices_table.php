<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uuid', 36)->unique();
            $table->string('invoice_number')->unique();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('server_id')->nullable(); // Related server
            
            // Invoice details
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->decimal('tax', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2)->default(0.00);
            $table->string('currency', 3)->default('USD');
            
            // Invoice period
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('due_date')->nullable();
            
            // Status
            $table->enum('status', ['draft', 'sent', 'paid', 'failed', 'cancelled'])->default('draft');
            
            // Billing information (snapshot at time of invoice)
            $table->string('billing_name')->nullable();
            $table->string('billing_email')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_country')->nullable();
            $table->string('billing_postal_code')->nullable();
            
            // Notes and attachments
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->timestamp('paid_at')->nullable();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('set null');
        });
        
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('invoice_id');
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 8, 2);
            $table->decimal('total_price', 10, 2);
            
            $table->timestamps();
            
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
}


