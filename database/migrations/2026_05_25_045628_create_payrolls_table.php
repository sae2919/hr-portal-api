<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->cascadeOnDelete();

            $table->foreignId('salary_structure_id')
                  ->nullable()
                  ->constrained('salary_structures')
                  ->nullOnDelete();

            $table->integer('month');
            $table->integer('year');

            $table->integer('working_days')->default(0);
            $table->integer('present_days')->default(0);
            $table->integer('leave_days')->default(0);

            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);

            $table->enum('status', [
                'draft',
                'processed',
                'paid'
            ])->default('draft');

            $table->timestamp('processed_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};