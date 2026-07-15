<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MlitPriceApi
{
    private const ENDPOINT = 'https://www.reinfolib.mlit.go.jp/ex-api/external/XIT001';

    /**
     * 国土交通省 不動産情報ライブラリの不動産取引価格情報を都道府県単位で取得する。
     *
     * @return array<int, array<string, mixed>>
     */
    public static function fetchByPrefecture(string $prefectureCode, int $year, int $quarter): array
    {
        $apiKey = config('services.mlit.api_key');

        if (! $apiKey) {
            Log::warning('MLIT不動産情報ライブラリのAPIキーが未設定です');

            return [];
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

            return [];
        }

        if (! $response->successful()) {
            Log::warning('MLIT不動産情報ライブラリの取得に失敗しました', [
                'status' => $response->status(),
                'prefecture_code' => $prefectureCode,
                'year' => $year,
                'quarter' => $quarter,
            ]);

            return [];
        }

        return $response->json('data') ?? [];
    }
}
