<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {

            $table->id();

            $table->foreignId('job_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->string('first_name');
            $table->string('last_name');

            $table->string('email')->unique();
            $table->string('phone')->nullable();

            $table->integer('experience')->default(0);

            $table->string('resume')->nullable();

            $table->enum('status', [
                'applied',
                'screening',
                'interview',
                'selected',
                'rejected'
            ])->default('applied');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};