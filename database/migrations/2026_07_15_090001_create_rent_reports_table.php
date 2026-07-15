<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rent_reports', function (Blueprint $table) {
            $table->id();
            $table->string('prefecture_code', 2);
            $table->string('prefecture_name');
            $table->string('city_name')->nullable();
            $table->string('layout', 20)->nullable();
            $table->decimal('area_sqm', 6, 2)->nullable();
            $table->unsignedInteger('rent_yen');
            $table->string('nickname')->default('匿名');
            $table->text('comment')->nullable();
            $table->string('ip_hash');
            $table->timestamps();

            $table->index('prefecture_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_reports');
    }
};
