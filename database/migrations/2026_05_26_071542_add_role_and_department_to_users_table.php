<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Adds seniority tier tracking ('admin', 'manager', 'employee')
            $table->string('role')->default('employee')->after('email');
            
            // Relates user record directly to their operational workgroup unit
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null')->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['role', 'department_id']);
        });
    }
};