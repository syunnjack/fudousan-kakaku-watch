<?php

namespace App\Support;

class RentEstimator
{
    /**
     * 不動産鑑定評価基準の積算法（基礎価格×期待利回り＋必要諸経費等）を単純化した概算値。
     * 必要諸経費（公租公課・損害保険料・維持修繕費・空室等損失相当額など）は含めていないため、
     * 実際の適正賃料より低めに出る点に注意。あくまで参考値として提示する。
     */
    private const DEFAULT_EXPECTED_YIELD = 0.05;

    public static function defaultExpectedYield(): float
    {
        return self::DEFAULT_EXPECTED_YIELD;
    }

    /**
     * @param  float  $pricePerSqm  取引価格の㎡単価（基礎価格の近似値として使用）
     * @param  float  $expectedYield  想定期待利回り（年率）
     * @return float  月額換算の㎡単価家賃概算（必要諸経費等は含まない）
     */
    public static function estimateMonthlyRentPerSqm(float $pricePerSqm, ?float $expectedYield = null): float
    {
        $yield = $expectedYield ?? self::DEFAULT_EXPECTED_YIELD;

        return $pricePerSqm * $yield / 12;
    }
}
