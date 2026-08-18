<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MlitPriceApi
{
    private const ENDPOINT = 'https://www.reinfolib.mlit.go.jp/ex-api/external/XIT001';

    /** 取得できた四半期の集計を保持する時間。四半期データは日中に変わらない。 */
    private const CACHE_TTL_HOURS = 12;

    /** まだ公開されていない四半期を覚えておく時間。公開後は早めに拾いたいので短くする。 */
    private const CACHE_TTL_MISSING_HOURS = 3;

    /**
     * 国土交通省 不動産情報ライブラリの不動産取引価格情報を都道府県単位で取得する。
     *
     * @return array<int, array<string, mixed>>
     */
    public static function fetchByPrefecture(string $prefectureCode, int $year, int $quarter): array
    {
        return self::request($prefectureCode, $year, $quarter)['records'];
    }

    /**
     * 都道府県・四半期ごとの㎡単価の平均と取引件数を返す。
     *
     * 画面はこの集計しか使わないのに、これまでは表示のたびに生の取引データを
     * 取り直していた（候補の四半期を順に試すため、1回の表示で最大5リクエスト）。
     * 集計だけをキャッシュして、国土交通省のAPIへの負荷と表示時間を抑える。
     *
     * 取得に失敗したときはキャッシュしない。障害を12時間「データ無し」として
     * 覚え込んでしまうため。
     *
     * @return array{year: int, quarter: int, avg_price_per_sqm: float, transaction_count: int}|null
     */
    public static function summaryByPrefecture(string $prefectureCode, int $year, int $quarter): ?array
    {
        $key = "mlit:price-summary:{$prefectureCode}:{$year}:{$quarter}";
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached['found'] ? $cached['summary'] : null;
        }

        $result = self::request($prefectureCode, $year, $quarter);

        if (! $result['ok']) {
            return null;
        }

        $avg = PriceStats::averagePricePerSqm($result['records']);

        if ($avg === null) {
            Cache::put($key, ['found' => false], now()->addHours(self::CACHE_TTL_MISSING_HOURS));

            return null;
        }

        $summary = [
            'year' => $year,
            'quarter' => $quarter,
            'avg_price_per_sqm' => $avg,
            'transaction_count' => PriceStats::transactionCount($result['records']),
        ];

        Cache::put($key, ['found' => true, 'summary' => $summary], now()->addHours(self::CACHE_TTL_HOURS));

        return $summary;
    }

    /**
     * @return array{ok: bool, records: array<int, array<string, mixed>>}
     */
    private static function request(string $prefectureCode, int $year, int $quarter): array
    {
        $apiKey = config('services.mlit.api_key');

        if (! $apiKey) {
            Log::warning('MLIT不動産情報ライブラリのAPIキーが未設定です');

            return ['ok' => false, 'records' => []];
        }

        try {
            $response = Http::withHeaders(['Ocp-Apim-Subscription-Key' => $apiKey])
                ->timeout(10)
                ->get(self::ENDPOINT, [
                    'year' => $year,
                    'quarter' => $quarter,
                    'area' => $prefectureCode,
                    'priceClassification' => '01',
                ]);
        } catch (ConnectionException $e) {
            Log::warning('MLIT不動産情報ライブラリへの接続に失敗しました', ['error' => $e->getMessage()]);

            return ['ok' => false, 'records' => []];
        }

        if (! $response->successful()) {
            Log::warning('MLIT不動産情報ライブラリの取得に失敗しました', [
                'status' => $response->status(),
                'prefecture_code' => $prefectureCode,
                'year' => $year,
                'quarter' => $quarter,
            ]);

            return ['ok' => false, 'records' => []];
        }

        return ['ok' => true, 'records' => $response->json('data') ?? []];
    }
}
