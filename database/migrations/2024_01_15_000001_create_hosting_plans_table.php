<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHostingPlansTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('hosting_plans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            
            // Server specifications
            $table->integer('memory')->unsigned()->default(0);
            $table->integer('swap')->unsigned()->default(0);
            $table->integer('disk')->unsigned()->default(0);
            $table->integer('io')->unsigned()->default(500);
            $table->integer('cpu')->unsigned()->default(0);
            $table->string('threads')->nullable();
            
            // Nest and Egg configuration
            $table->unsignedInteger('nest_id');
            $table->unsignedInteger('egg_id');
            
            // Database and allocation limits
            $table->unsignedInteger('database_limit')->default(0);
            $table->unsignedInteger('allocation_limit')->default(0);
            $table->unsignedInteger('backup_limit')->default(0);
            
            // Pricing
            $table->decimal('price', 8, 2)->default(0.00);
            $table->string('billing_period')->default('monthly'); // monthly, quarterly, semi_annually, annually
            
            // Feature flags
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            
            // Stock management
            $table->integer('stock_limit')->nullable(); // null = unlimited
            $table->integer('current_stock')->default(0);
            
            $table->timestamps();
            
            $table->foreign('nest_id')->references('id')->on('nests')->onDelete('cascade');
            $table->foreign('egg_id')->references('id')->on('eggs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('hosting_plans');
    }
}


