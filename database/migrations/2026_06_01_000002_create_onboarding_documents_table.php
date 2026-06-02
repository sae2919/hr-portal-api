<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOnboardingDocumentsTable extends Migration
{
    public function up()
    {
        Schema::create('onboarding_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('onboarding_request_id');
            $table->string('document_type'); // resume, offer_letter, id_proof, address_proof, degree, etc.
            $table->string('original_name');
            $table->string('file_path');
            $table->string('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('verification_notes')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->foreign('onboarding_request_id')->references('id')->on('onboarding_requests')->onDelete('cascade');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
            $table->index('document_type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('onboarding_documents');
    }
}