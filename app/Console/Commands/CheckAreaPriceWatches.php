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

            // 画面と同じ、住まいに関する取引の中央値で判定する。
            // 種類を問わない平均だったころは、農地の取引が多い四半期に
            // 値段が動いたとして通知されてしまう可能性があった。
            //
            // 代表となる種類が前回と違うときは通知しない。
            // マンションの単価と宅地の単価を比べても意味が無いため。
            $sameType = $watch->last_representative_type === null
                || $watch->last_representative_type === $latest['representative_type'];

            if ($sameType && $watch->last_avg_price_per_sqm !== null && $watch->last_avg_price_per_sqm > 0) {
                $changeRate = ($latest['representative_median'] - $watch->last_avg_price_per_sqm) / $watch->last_avg_price_per_sqm;

                if (abs($changeRate) >= self::CHANGE_THRESHOLD) {
                    $direction = $changeRate > 0 ? '上昇' : '下落';
                    $percent = number_format(abs($changeRate) * 100, 1);
                    $price = number_format((int) round($latest['representative_median']));
                    $type = $latest['representative_type'];

                    LineMessaging::push(
                        $watch->lineUser->line_user_id,
                        "「{$watch->prefecture_name}」の{$type}の取引価格（㎡単価の中央値）が前回比{$percent}%{$direction}し、約{$price}円/㎡になりました。"
                    );
                }
            }

            $watch->update([
                'last_avg_price_per_sqm' => (int) round($latest['representative_median']),
                'last_representative_type' => $latest['representative_type'],
                'last_checked_year' => $latest['year'],
                'last_checked_quarter' => $latest['quarter'],
                'last_checked_at' => now(),
            ]);
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findLatestData(string $prefectureCode): ?array
    {
        foreach (QuarterHelper::candidateQuarters(4) as [$year, $quarter]) {
            // 画面と同じ集計（キャッシュ付き）を使う。通知の判定と表示がずれないようにする。
            $summary = MlitPriceApi::summaryByPrefecture($prefectureCode, $year, $quarter);

            if ($summary !== null) {
                return $summary;
            }
        }

        return null;
    }
}
