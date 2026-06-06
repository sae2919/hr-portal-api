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
            $table->foreignId('old_designation_id')->nullable()->after('old_employment_type')->constrained('designations')->nullOnDelete();
            $table->foreignId('new_designation_id')->nullable()->after('new_employment_type')->constrained('designations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_revisions', function (Blueprint $table) {
            $table->dropForeign(['old_designation_id']);
            $table->dropForeign(['new_designation_id']);
            $table->dropColumn(['old_designation_id', 'new_designation_id']);
        });
    }
};
