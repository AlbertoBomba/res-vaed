<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
        });

        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('sport', 30);
            $table->string('type', 30);
            $table->string('surface', 30);
            $table->string('enclosure', 30);
            $table->string('status', 20)->default('active');
            $table->unsignedSmallInteger('capacity')->default(4);
            $table->boolean('lighting')->default(false);
            $table->boolean('accessible')->default(false);
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('hourly_price_cents');
            $table->unsignedInteger('two_hour_price_cents');
            $table->unsignedInteger('three_plus_hour_price_cents');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'status']);
        });

        Schema::create('court_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->unsignedTinyInteger('hour');
            $table->unique(['court_id', 'weekday', 'hour']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_hours');
        Schema::dropIfExists('courts');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
        Schema::dropIfExists('companies');
    }
};
