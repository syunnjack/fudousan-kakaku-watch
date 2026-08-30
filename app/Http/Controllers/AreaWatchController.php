<?php

namespace App\Http\Controllers;

use App\Models\AreaWatch;
use App\Support\MlitPriceApi;
use App\Support\PriceStats;
use App\Support\Prefectures;
use App\Support\QuarterHelper;
use App\Support\RentEstimator;
use Illuminate\Http\Request;

class AreaWatchController extends Controller
{
    public function index()
    {
        $prefectures = Prefectures::all();

        return view('watch.index', compact('prefectures'));
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'prefecture_code' => 'required|string|size:2',
        ]);
        $prefectureCode = $validated['prefecture_code'];
        $prefectureName = Prefectures::name($prefectureCode);

        if (! $prefectureName) {
            return redirect()->route('watch.index')->withErrors(['prefecture_code' => '都道府県が見つかりませんでした。']);
        }

        $latest = $this->findLatestData($prefectureCode);

        // 直近から遡って最大4四半期（1年）ぶん。1四半期だけを見せても
        // 高いのか安いのかが分からないため、推移として並べる。
        //
        // キャッシュが空だとこの分だけ国土交通省のAPIを呼ぶので、初回表示は
        // 数秒かかる。.github/workflows/warm-cache.yml で定期的に温めている。
        $history = $latest ? $this->history($prefectureCode, $latest, 4) : [];
        $previous = $history[1] ?? null;

        $changeRate = self::changeRateBetween($latest, $previous);

        $isWatching = session('line_user_local_id')
            ? AreaWatch::where('line_user_id', session('line_user_local_id'))
                ->where('prefecture_code', $prefectureCode)
                ->exists()
            : false;

        // 家賃の概算は住まいに関する取引の中央値から出す。
        // 種類を問わない平均を使っていたころは、農地が過半を占める県で
        // 実態とかけ離れた金額になっていた。
        $estimatedMonthlyRentPerSqm = $latest
            ? RentEstimator::estimateMonthlyRentPerSqm($latest['representative_median'])
            : null;

        return view('watch.results', [
            'prefectureCode' => $prefectureCode,
            'prefectureName' => $prefectureName,
            'latest' => $latest,
            'previous' => $previous,
            'history' => $history,
            'changeRate' => $changeRate,
            'isWatching' => $isWatching,
            'estimatedMonthlyRentPerSqm' => $estimatedMonthlyRentPerSqm,
            'expectedYield' => RentEstimator::defaultExpectedYield(),
            'residentialTypes' => PriceStats::RESIDENTIAL_TYPES,
        ]);
    }

    public function sitemap()
    {
        $prefectures = Prefectures::all();

        return response()
            ->view('sitemap', compact('prefectures'))
            ->header('Content-Type', 'text/xml');
    }

    /**
     * 直近の参照候補四半期を順に試し、取引データが存在する最新の四半期を返す。
     *
     * @return array<string, mixed>|null
     */
    private function findLatestData(string $prefectureCode): ?array
    {
        foreach (QuarterHelper::candidateQuarters(4) as [$year, $quarter]) {
            $result = $this->tryFetch($prefectureCode, $year, $quarter);
            if ($result) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tryFetch(string $prefectureCode, int $year, int $quarter): ?array
    {
        // 集計はキャッシュされる。表示のたびに国土交通省のAPIを叩かない。
        return MlitPriceApi::summaryByPrefecture($prefectureCode, $year, $quarter);
    }

    /**
     * 最新の四半期から遡って、データのある四半期の集計を新しい順に返す。
     *
     * データが無い四半期は飛ばさずに打ち切る。間が抜けた並びを「推移」として
     * 見せると、連続した期間の変化に見えてしまうため。
     *
     * @param  array<string, mixed>  $latest
     * @return array<int, array<string, mixed>>
     */
    private function history(string $prefectureCode, array $latest, int $quarters): array
    {
        $history = [$latest];
        [$year, $quarter] = [$latest['year'], $latest['quarter']];

        for ($i = 1; $i < $quarters; $i++) {
            [$year, $quarter] = self::previousQuarterOf($year, $quarter);
            $summary = $this->tryFetch($prefectureCode, $year, $quarter);

            if (! $summary) {
                break;
            }

            $history[] = $summary;
        }

        return $history;
    }

    /**
     * 代表となる種類の中央値どうしで前四半期比を出す。
     * 種類が入れ替わった四半期は比較しない（マンションの単価と宅地の単価を
     * 比べても意味が無いため）。
     *
     * @param  array<string, mixed>|null  $latest
     * @param  array<string, mixed>|null  $previous
     */
    private static function changeRateBetween(?array $latest, ?array $previous): ?float
    {
        if (! $latest || ! $previous) {
            return null;
        }

        if ($latest['representative_type'] !== $previous['representative_type']) {
            return null;
        }

        if (! $previous['representative_median'] || $previous['representative_median'] <= 0) {
            return null;
        }

        return ($latest['representative_median'] - $previous['representative_median'])
            / $previous['representative_median'];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function previousQuarterOf(int $year, int $quarter): array
    {
        $totalQuarters = ($year * 4 + ($quarter - 1)) - 1;

        return [intdiv($totalQuarters, 4), ($totalQuarters % 4) + 1];
    }
}
