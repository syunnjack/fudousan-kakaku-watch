<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 前回の通知判定に使った「代表となる取引の種類」を覚えておく。
     *
     * 中央値は種類ごとに桁が違うため、前回がマンション・今回が宅地といったときに
     * 比較すると、実際には動いていない価格を「大幅変動」として通知してしまう。
     */
    public function up(): void
    {
        Schema::table('area_watches', function (Blueprint $table) {
            $table->string('last_representative_type')->nullable()->after('last_avg_price_per_sqm');
        });
    }

    public function down(): void
    {
        Schema::table('area_watches', function (Blueprint $table) {
            $table->dropColumn('last_representative_type');
        });
    }
};
