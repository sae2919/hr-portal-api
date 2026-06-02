<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->decimal('value_numeric', 5, 2)->nullable()->after('value');
        });

        // Seed pf_percentage = 12 if it doesn't exist yet
        DB::table('company_settings')->upsert(
            [['key' => 'pf_percentage', 'value' => '12', 'value_numeric' => 12.00]],
            ['key'],
            ['value', 'value_numeric']
        );
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn('value_numeric');
        });
    }
};