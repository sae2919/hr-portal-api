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
        Schema::table('onboarding_requests', function (Blueprint $table) {
            $table->string('access_token')->nullable()->unique()->after('id');
            $table->timestamp('link_expires_at')->nullable()->after('personal_details');
        });

        // Backfill existing records
        $requests = \App\Models\OnboardingRequest::all();
        foreach ($requests as $request) {
            if (!$request->access_token) {
                $request->access_token = \Illuminate\Support\Str::random(40);
                // Set expiry for existing requests to be 48 hours from their creation date
                $request->link_expires_at = $request->created_at ? $request->created_at->addHours(48) : now()->addHours(48);
                $request->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('onboarding_requests', function (Blueprint $table) {
            $table->dropColumn(['access_token', 'link_expires_at']);
        });
    }
};
