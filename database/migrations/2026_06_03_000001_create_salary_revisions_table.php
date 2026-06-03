<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            
            // Old salary values
            $table->decimal('old_basic_salary', 12, 2)->default(0);
            $table->decimal('old_hra', 12, 2)->default(0);
            $table->decimal('old_allowances', 12, 2)->default(0);
            $table->decimal('old_bonus', 12, 2)->default(0);
            $table->decimal('old_gross_salary', 12, 2)->default(0);
            $table->decimal('old_net_salary', 12, 2)->default(0);
            
            // New salary values
            $table->decimal('new_basic_salary', 12, 2)->default(0);
            $table->decimal('new_hra', 12, 2)->default(0);
            $table->decimal('new_allowances', 12, 2)->default(0);
            $table->decimal('new_bonus', 12, 2)->default(0);
            $table->decimal('new_gross_salary', 12, 2)->default(0);
            $table->decimal('new_net_salary', 12, 2)->default(0);
            
            $table->decimal('increment_percentage', 5, 2)->default(0);
            $table->date('effective_date');
            $table->string('reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_revisions');
    }
};
