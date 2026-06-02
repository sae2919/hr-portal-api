<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Seed default values
        DB::table('company_settings')->insert([
            [
                'key'         => 'pf_enabled',
                'value'       => '1',
                'description' => 'Enable Provident Fund deduction in auto payroll generation',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'pt_enabled',
                'value'       => '1',
                'description' => 'Enable Professional Tax deduction in auto payroll generation',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};