<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOnboardingRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('onboarding_requests', function (Blueprint $table) {
            $table->id();
            $table->string('candidate_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('position');
            $table->string('department');
            $table->date('joining_date');
            $table->decimal('ctc', 12, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'onboarded'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index('status');
            $table->index('joining_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('onboarding_requests');
    }
}