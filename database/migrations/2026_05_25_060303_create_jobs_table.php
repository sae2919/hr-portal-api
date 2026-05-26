<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {

            $table->id();

            $table->string('title');

            $table->foreignId('department_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->text('description')->nullable();

            $table->integer('vacancies')->default(1);

            $table->decimal('salary_from', 12, 2)->nullable();
            $table->decimal('salary_to', 12, 2)->nullable();

            $table->enum('status', [
                'open',
                'closed',
                'draft'
            ])->default('open');

            $table->date('deadline')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};