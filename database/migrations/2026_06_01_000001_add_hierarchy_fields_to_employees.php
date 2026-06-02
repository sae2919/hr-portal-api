<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHierarchyFieldsToEmployees extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            // Add position level (if not exists)
            if (!Schema::hasColumn('employees', 'position_level')) {
                $table->enum('position_level', [
                    'c_level', 'vp', 'director', 'senior_manager', 
                    'manager', 'team_lead', 'staff', 'intern'
                ])->default('staff')->after('reporting_to');
            }
            
            // Add hierarchy level (1=CEO, 2=Director, 3=Manager, 4=Team Lead, 5=Staff)
            if (!Schema::hasColumn('employees', 'hierarchy_level')) {
                $table->integer('hierarchy_level')->default(5)->after('position_level');
            }
            
            // Add hierarchy path for quick queries
            if (!Schema::hasColumn('employees', 'hierarchy_path')) {
                $table->string('hierarchy_path')->nullable()->after('hierarchy_level');
            }
            
            // Add indexes for performance
            $table->index('position_level');
            $table->index('hierarchy_level');
            $table->index('reporting_to');
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['position_level', 'hierarchy_level', 'hierarchy_path']);
            $table->dropIndex(['position_level']);
            $table->dropIndex(['hierarchy_level']);
            $table->dropIndex(['reporting_to']);
        });
    }
}