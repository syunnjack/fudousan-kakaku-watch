<?php

namespace App\Http\Controllers;

use App\Models\AreaWatch;
use App\Support\MlitPriceApi;
use App\Support\PriceStats;
use App\Support\Prefectures;
use App\Support\QuarterHelper;
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
        $previous = null;

        if ($latest) {
            [$latestYear, $latestQuarter] = [$latest['year'], $latest['quarter']];
            [$prevYear, $prevQuarter] = self::previousQuarterOf($latestYear, $latestQuarter);
            $previous = $this->tryFetch($prefectureCode, $prevYear, $prevQuarter);
        }

        $changeRate = ($latest && $previous && $previous['avg_price_per_sqm'] > 0)
            ? ($latest['avg_price_per_sqm'] - $previous['avg_price_per_sqm']) / $previous['avg_price_per_sqm']
            : null;

        $isWatching = session('line_user_local_id')
            ? AreaWatch::where('line_user_id', session('line_user_local_id'))
                ->where('prefecture_code', $prefectureCode)
                ->exists()
            : false;

        return view('watch.results', [
            'prefectureCode' => $prefectureCode,
            'prefectureName' => $prefectureName,
            'latest' => $latest,
            'previous' => $previous,
            'changeRate' => $changeRate,
            'isWatching' => $isWatching,
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
     * @return array{year: int, quarter: int, avg_price_per_sqm: float, transaction_count: int}|null
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
     * @return array{year: int, quarter: int, avg_price_per_sqm: float, transaction_count: int}|null
     */
    private function tryFetch(string $prefectureCode, int $year, int $quarter): ?array
    {
        $records = MlitPriceApi::fetchByPrefecture($prefectureCode, $year, $quarter);
        $avg = PriceStats::averagePricePerSqm($records);

        if ($avg === null) {
            return null;
        }

        return [
            'year' => $year,
            'quarter' => $quarter,
            'avg_price_per_sqm' => $avg,
            'transaction_count' => PriceStats::transactionCount($records),
        ];
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
