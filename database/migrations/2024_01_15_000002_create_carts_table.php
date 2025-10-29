<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCartsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uuid', 36)->unique();
            $table->string('session_token')->nullable()->index(); // For guest users
            $table->unsignedInteger('user_id')->nullable();
            
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        
        Schema::create('cart_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cart_id');
            $table->unsignedInteger('plan_id');
            $table->integer('quantity')->default(1);
            
            // Store snapshot of plan pricing at time of cart addition
            $table->decimal('price_at_time', 8, 2);
            
            $table->timestamps();
            
            $table->foreign('cart_id')->references('id')->on('carts')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('hosting_plans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
}


