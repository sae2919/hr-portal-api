<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOfferLettersTable extends Migration
{
    public function up()
    {
        Schema::create('offer_letters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('onboarding_request_id');
            $table->string('letter_number')->unique();
            $table->date('letter_date');
            $table->string('file_path');
            $table->enum('status', ['draft', 'sent', 'accepted', 'expired'])->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            $table->foreign('onboarding_request_id')->references('id')->on('onboarding_requests')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('offer_letters');
    }
}