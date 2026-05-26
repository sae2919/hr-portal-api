<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payroll_id')
                  ->constrained('payrolls')
                  ->cascadeOnDelete();

            $table->string('name');

            $table->enum('type', [
                'earning',
                'deduction'
            ]);

            $table->decimal('amount', 12, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};