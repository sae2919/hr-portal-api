<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->cascadeOnDelete();

            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('hra', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);

            $table->decimal('pf_deduction', 12, 2)->default(0);
            $table->decimal('tax_deduction', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);

            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);

            $table->date('effective_from');
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structures');
    }
};