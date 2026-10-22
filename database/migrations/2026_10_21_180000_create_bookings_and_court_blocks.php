<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('court_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedTinyInteger('duration_hours');
            $table->integer('price_cents');
            $table->string('status', 20)->default('confirmed');
            $table->timestamps();
            $table->index(['company_id', 'court_id', 'starts_at']);
            $table->index(['user_id', 'court_id', 'date']);
        });

        Schema::create('court_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('court_id')->constrained()->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'court_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_blocks');
        Schema::dropIfExists('bookings');
    }
};
