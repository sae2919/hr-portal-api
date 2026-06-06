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
            $table->enum('onboarding_type', ['full_time', 'intern', 'free_intern'])->default('full_time')->after('ctc');
            $table->string('custom_heading')->nullable()->after('onboarding_type');
            $table->json('required_documents')->nullable()->after('custom_heading');
            $table->json('optional_documents')->nullable()->after('required_documents');
            $table->json('custom_document_labels')->nullable()->after('optional_documents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('onboarding_requests', function (Blueprint $table) {
            $table->dropColumn([
                'onboarding_type',
                'custom_heading',
                'required_documents',
                'optional_documents',
                'custom_document_labels'
            ]);
        });
    }
};
