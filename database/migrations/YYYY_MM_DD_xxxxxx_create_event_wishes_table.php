<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_wishes', function (Blueprint $table) {
            $table->id();

            // Who is being wished (the birthday/anniversary person)
            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->onDelete('cascade');

            // Who sent the wish
            $table->foreignId('sender_id')
                  ->constrained('employees')
                  ->onDelete('cascade');

            $table->enum('wish_type', ['birthday', 'anniversary']);
            $table->text('message');
            $table->string('emoji', 10)->nullable();

            $table->timestamps();

            // Fast lookup: all wishes for a person on a given day
            $table->index(['employee_id', 'wish_type', 'created_at']);

            // Fast duplicate-check lookup
            $table->index(['sender_id', 'employee_id', 'wish_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_wishes');
    }
};