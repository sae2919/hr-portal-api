<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChargerSimToAssetsAndAllocations extends Migration
{
    public function up()
    {
        // Add charger/SIM fields to assets table
        Schema::table('assets', function (Blueprint $table) {
            $table->boolean('has_charger')->nullable()->after('specifications');
            $table->boolean('has_sim')->nullable()->after('has_charger');
        });

        // Add charger/SIM given fields to asset_allocations table
        Schema::table('asset_allocations', function (Blueprint $table) {
            $table->boolean('charger_given')->nullable()->after('condition_notes');
            $table->boolean('sim_given')->nullable()->after('charger_given');
        });
    }

    public function down()
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['has_charger', 'has_sim']);
        });

        Schema::table('asset_allocations', function (Blueprint $table) {
            $table->dropColumn(['charger_given', 'sim_given']);
        });
    }
}
