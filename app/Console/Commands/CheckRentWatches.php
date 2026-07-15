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

            $since = $watch->last_checked_at ?? $watch->created_at;
            $newReports = RentReport::where('prefecture_code', $watch->prefecture_code)
                ->where('created_at', '>', $since)
                ->get();

            if ($newReports->isNotEmpty()) {
                $latest = $newReports->first();
                LineMessaging::push(
                    $watch->lineUser->line_user_id,
                    "「{$watch->prefecture_name}」の新しい家賃口コミが投稿されました: " . number_format($latest->rent_yen) . '円'
                    . ($latest->layout ? "（{$latest->layout}）" : '')
                );

                // last_checked_atは「実際に検知した最新レポートの時刻」まで進める。
                // now()まで無条件に進めると、チェック実行と同一秒内に投稿されたレポートが
                // 次回以降も since より前の扱いとなり永久に検知漏れになるため。
                $watch->update(['last_checked_at' => $newReports->max('created_at')]);
            }
        }

        return self::SUCCESS;
    }
}
