<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetAllocationsTable extends Migration
{
    public function up()
    {
        Schema::create('asset_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('onboarding_request_id')->nullable();
            $table->date('allocated_date');
            $table->date('return_date')->nullable();
            $table->enum('status', ['allocated', 'returned', 'lost', 'damaged'])->default('allocated');
            $table->text('condition_notes')->nullable();
            $table->text('return_notes')->nullable();
            $table->unsignedBigInteger('allocated_by');
            $table->timestamps();
            
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('onboarding_request_id')->references('id')->on('onboarding_requests')->onDelete('set null');
            $table->foreign('allocated_by')->references('id')->on('users');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('asset_allocations');
    }
}