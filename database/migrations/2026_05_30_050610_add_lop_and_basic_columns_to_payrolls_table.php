<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Add only if they don't exist — safe to run on fresh or existing installs
            if (!Schema::hasColumn('payrolls', 'lop_days')) {
                $table->decimal('lop_days', 8, 2)->default(0)->after('leave_days');
            }
            if (!Schema::hasColumn('payrolls', 'lop_deduction')) {
                $table->decimal('lop_deduction', 10, 2)->default(0)->after('lop_days');
            }
            if (!Schema::hasColumn('payrolls', 'basic_salary')) {
                $table->decimal('basic_salary', 10, 2)->default(0)->after('lop_deduction');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['lop_days', 'lop_deduction', 'basic_salary']);
        });
    }
};