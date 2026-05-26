<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {

            $table->id();

            $table->foreignId('candidate_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->date('interview_date');

            $table->string('mode')->nullable();

            $table->string('interviewer')->nullable();

            $table->text('feedback')->nullable();

            $table->enum('result', [
                'pending',
                'passed',
                'failed'
            ])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};