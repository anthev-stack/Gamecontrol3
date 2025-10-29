<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServerBillingSharesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('server_billing_shares', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('server_id');
            $table->unsignedInteger('user_id');
            $table->decimal('share_percentage', 5, 2)->default(50.00); // 50% for 50/50 split
            $table->enum('status', ['active', 'pending', 'cancelled'])->default('active');
            $table->boolean('has_server_access')->default(true);
            $table->timestamps();
            
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->unique(['server_id', 'user_id']);
        });
        
        Schema::create('billing_invitations', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uuid', 36)->unique();
            $table->string('token', 64)->unique();
            $table->unsignedInteger('server_id');
            $table->unsignedInteger('inviter_id'); // User who sent invite
            $table->string('invitee_email');
            $table->unsignedInteger('invitee_user_id')->nullable(); // Filled when accepted
            $table->decimal('share_percentage', 5, 2)->default(50.00);
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired', 'cancelled'])->default('pending');
            $table->text('message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
            $table->foreign('inviter_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('invitee_user_id')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['invitee_email', 'status']);
            $table->index(['token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('billing_invitations');
        Schema::dropIfExists('server_billing_shares');
    }
}

