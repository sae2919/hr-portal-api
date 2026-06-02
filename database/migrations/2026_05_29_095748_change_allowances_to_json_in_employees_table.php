<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Change allowances from decimal to JSON
            $table->json('allowances')->nullable()->change();
        });
        
        // Optional: Convert existing decimal values to JSON format
        DB::table('employees')->chunkById(100, function ($employees) {
            foreach ($employees as $emp) {
                if (is_numeric($emp->allowances) && $emp->allowances > 0) {
                    // Convert old decimal to JSON array with "other" type
                    DB::table('employees')->where('id', $emp->id)->update([
                        'allowances' => json_encode([
                            ['type' => 'other', 'amount' => floatval($emp->allowances)]
                        ])
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Revert: extract total from JSON back to decimal
            $table->decimal('allowances', 10, 2)->default(0)->change();
        });
        
        DB::table('employees')->chunkById(100, function ($employees) {
            foreach ($employees as $emp) {
                if (is_string($emp->allowances)) {
                    $total = collect(json_decode($emp->allowances, true))->sum('amount');
                    DB::table('employees')->where('id', $emp->id)->update([
                        'allowances' => $total
                    ]);
                }
            }
        });
    }
};