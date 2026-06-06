<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('salary_revisions', function (Blueprint $table) {
            $table->string('old_employment_type')->nullable()->after('old_net_salary');
            $table->string('new_employment_type')->nullable()->after('new_net_salary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_revisions', function (Blueprint $table) {
            $table->dropColumn(['old_employment_type', 'new_employment_type']);
        });
    }
};
