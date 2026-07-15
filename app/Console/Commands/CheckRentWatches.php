<?php

namespace App\Console\Commands;

use App\Models\RentReport;
use App\Models\RentWatch;
use App\Support\LineMessaging;
use Illuminate\Console\Command;

class CheckRentWatches extends Command
{
    protected $signature = 'rent:check-watches';

    protected $description = 'ウォッチ登録された都道府県に新しい家賃口コミが投稿されていないか確認し、LINEで通知する';

    public function handle(): int
    {
        $watches = RentWatch::with('lineUser')->get();

        foreach ($watches as $watch) {
            if (! $watch->lineUser) {
                continue;
            }

            $since = $watch->last_checked_report_id ?? 0;
            $newReports = RentReport::where('prefecture_code', $watch->prefecture_code)
                ->where('id', '>', $since)
                ->get();

            if ($newReports->isEmpty()) {
                continue;
            }

            $latest = $newReports->sortByDesc('id')->first();
            LineMessaging::push(
                $watch->lineUser->line_user_id,
                "「{$watch->prefecture_name}」の新しい家賃口コミが投稿されました: " . number_format($latest->rent_yen) . '円'
                . ($latest->layout ? "（{$latest->layout}）" : '')
            );

            // last_checked_report_idは検知カーソル。idは常に厳密単調増加のため、
            // created_at(秒精度)を使った場合に起こりうる同一秒内の複数投稿の取りこぼしが起きない。
            $watch->update(['last_checked_report_id' => $newReports->max('id')]);
        }

        return self::SUCCESS;
    }
}
