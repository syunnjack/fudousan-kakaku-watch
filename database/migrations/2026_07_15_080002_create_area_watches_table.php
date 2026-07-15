<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('area_watches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_user_id')->constrained('line_users')->cascadeOnDelete();
            $table->string('prefecture_code', 2);
            $table->string('prefecture_name');
            $table->unsignedBigInteger('last_avg_price_per_sqm')->nullable();
            $table->unsignedSmallInteger('last_checked_year')->nullable();
            $table->unsignedTinyInteger('last_checked_quarter')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->unique(['line_user_id', 'prefecture_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_watches');
    }
};
