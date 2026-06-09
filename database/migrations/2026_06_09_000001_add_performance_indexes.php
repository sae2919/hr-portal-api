<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // employees — frequently filtered by status, department, reporting_to
        Schema::table('employees', function (Blueprint $table) {
            $table->index(['status', 'department_id'], 'idx_employees_status_dept');
            $table->index('reporting_to', 'idx_employees_reporting_to');
            $table->index('employment_type', 'idx_employees_employment_type');
        });

        // attendances — dashboard stats queries by date + status every page load
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['date', 'status'], 'idx_attendances_date_status');
            $table->index('employee_id', 'idx_attendances_employee_id');
        });

        // leaves — dashboard on-leave / pending counts + leave list filters
        Schema::table('leaves', function (Blueprint $table) {
            $table->index(['status', 'start_date', 'end_date'], 'idx_leaves_status_dates');
            $table->index('employee_id', 'idx_leaves_employee_id');
        });

        // payrolls — filtered by employee, month, year on every payroll page load
        Schema::table('payrolls', function (Blueprint $table) {
            $table->index(['employee_id', 'month', 'year'], 'idx_payrolls_emp_month_year');
            $table->index(['month', 'year'], 'idx_payrolls_month_year');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('idx_employees_status_dept');
            $table->dropIndex('idx_employees_reporting_to');
            $table->dropIndex('idx_employees_employment_type');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_attendances_date_status');
            $table->dropIndex('idx_attendances_employee_id');
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->dropIndex('idx_leaves_status_dates');
            $table->dropIndex('idx_leaves_employee_id');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropIndex('idx_payrolls_emp_month_year');
            $table->dropIndex('idx_payrolls_month_year');
        });
    }
};
