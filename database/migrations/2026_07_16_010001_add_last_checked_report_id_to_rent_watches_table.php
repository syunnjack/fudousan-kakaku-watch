<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rent_watches', function (Blueprint $table) {
            // created_at(秒精度)の比較では、同一秒内に複数件のRentReportが投稿されると
            // 後続の投稿を永久に検知できなくなる恐れがあるため、常に厳密単調増加する
            // rent_reports.idを検知カーソルとして使う方式に変更する。
            $table->unsignedBigInteger('last_checked_report_id')->nullable()->after('prefecture_name');
            $table->dropColumn('last_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('rent_watches', function (Blueprint $table) {
            $table->timestamp('last_checked_at')->nullable();
            $table->dropColumn('last_checked_report_id');
        });
    }
};
