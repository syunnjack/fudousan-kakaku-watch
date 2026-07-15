<?php

namespace App\Console\Commands;

use App\Models\AreaWatch;
use App\Support\LineMessaging;
use App\Support\MlitPriceApi;
use App\Support\PriceStats;
use App\Support\QuarterHelper;
use Illuminate\Console\Command;

class CheckAreaPriceWatches extends Command
{
    protected $signature = 'watch:check-area-prices';

    protected $description = 'ウォッチ登録された都道府県の不動産取引価格（㎡単価）を確認し、前回比10%以上変動していればLINEで通知する';

    private const CHANGE_THRESHOLD = 0.10;

    public function handle(): int
    {
        $watches = AreaWatch::with('lineUser')->get();

        foreach ($watches as $watch) {
            if (! $watch->lineUser) {
                continue;
            }

            $latest = $this->findLatestData($watch->prefecture_code);

            if (! $latest) {
                continue;
            }

            if ($watch->last_avg_price_per_sqm !== null && $watch->last_avg_price_per_sqm > 0) {
                $changeRate = ($latest['avg_price_per_sqm'] - $watch->last_avg_price_per_sqm) / $watch->last_avg_price_per_sqm;

                if (abs($changeRate) >= self::CHANGE_THRESHOLD) {
                    $direction = $changeRate > 0 ? '上昇' : '下落';
                    $percent = number_format(abs($changeRate) * 100, 1);
                    $price = number_format((int) round($latest['avg_price_per_sqm']));

                    LineMessaging::push(
                        $watch->lineUser->line_user_id,
                        "「{$watch->prefecture_name}」の不動産取引価格（㎡単価）が前回比{$percent}%{$direction}し、約{$price}円/㎡になりました。"
                    );
                }
            }

            $watch->update([
                'last_avg_price_per_sqm' => (int) round($latest['avg_price_per_sqm']),
                'last_checked_year' => $latest['year'],
                'last_checked_quarter' => $latest['quarter'],
                'last_checked_at' => now(),
            ]);
        }

        return self::SUCCESS;
    }

    /**
     * @return array{year: int, quarter: int, avg_price_per_sqm: float}|null
     */
    private function findLatestData(string $prefectureCode): ?array
    {
        foreach (QuarterHelper::candidateQuarters(4) as [$year, $quarter]) {
            $records = MlitPriceApi::fetchByPrefecture($prefectureCode, $year, $quarter);
            $avg = PriceStats::averagePricePerSqm($records);

            if ($avg !== null) {
                return ['year' => $year, 'quarter' => $quarter, 'avg_price_per_sqm' => $avg];
            }
        }

        return null;
    }
}
