<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->enum('team_lead_status', ['pending', 'approved', 'rejected'])
                  ->default('pending')
                  ->after('status');

            $table->foreignId('team_lead_id')
                  ->nullable()
                  ->after('team_lead_status')
                  ->constrained('users')
                  ->nullOnDelete();

            $table->text('team_lead_rejection_reason')
                  ->nullable()
                  ->after('team_lead_id');

            $table->timestamp('team_lead_acted_at')
                  ->nullable()
                  ->after('team_lead_rejection_reason');

            $table->boolean('hr_override')
                  ->default(false)
                  ->after('team_lead_acted_at');
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropForeign(['team_lead_id']);
            $table->dropColumn([
                'team_lead_status',
                'team_lead_id',
                'team_lead_rejection_reason',
                'team_lead_acted_at',
                'hr_override',
            ]);
        });
    }
};