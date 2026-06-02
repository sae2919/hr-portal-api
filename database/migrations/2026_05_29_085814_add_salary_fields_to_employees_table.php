<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedTinyInteger('pf_percentage')->default(0)->after('bank_branch');
            $table->decimal('pf_deduction', 10, 2)->default(0)->after('pf_percentage');
            $table->decimal('basic_salary', 10, 2)->default(0)->after('pf_deduction');
            $table->decimal('hra', 10, 2)->default(0)->after('basic_salary');
            $table->decimal('allowances', 10, 2)->default(0)->after('hra');
            $table->decimal('bonus', 10, 2)->default(0)->after('allowances');
            $table->decimal('esi_employee', 10, 2)->default(0)->after('bonus');
            $table->decimal('esi_employer', 10, 2)->default(0)->after('esi_employee');
            $table->decimal('pt_amount', 10, 2)->default(0)->after('esi_employer');
            $table->string('pt_state')->nullable()->after('pt_amount');
            $table->decimal('tds_amount', 10, 2)->default(0)->after('pt_state');
            $table->decimal('other_deductions', 10, 2)->default(0)->after('tds_amount');
            $table->decimal('ctc', 12, 2)->default(0)->after('other_deductions');
            $table->string('pan_number')->nullable()->after('ctc');
            $table->string('aadhaar_number')->nullable()->after('pan_number');
            $table->string('driving_license')->nullable()->after('aadhaar_number');
            $table->string('passport_number')->nullable()->after('driving_license');
            $table->string('voter_id')->nullable()->after('passport_number');
            $table->string('uan_number')->nullable()->after('voter_id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'pf_percentage','pf_deduction','basic_salary','hra','allowances','bonus',
                'esi_employee','esi_employer','pt_amount','pt_state','tds_amount',
                'other_deductions','ctc','pan_number','aadhaar_number',
                'driving_license','passport_number','voter_id','uan_number',
            ]);
        });
    }
};