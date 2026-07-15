<?php

namespace App\Support;

class PriceStats
{
    /**
     * 取引価格レコード群から、取引価格(TradePrice)÷面積(Area)の平均を㎡単価として算出する。
     * 土地・建物などで規模が大きく異なる生の取引価格そのままでは比較指標にならないため。
     *
     * @param  array<int, array<string, mixed>>  $records
     */
    public static function averagePricePerSqm(array $records): ?float
    {
        $unitPrices = [];

        foreach ($records as $record) {
            $price = self::toNumber($record['TradePrice'] ?? null);
            $area = self::toNumber($record['Area'] ?? null);

            if ($price !== null && $area !== null && $area > 0) {
                $unitPrices[] = $price / $area;
            }
        }

        if (empty($unitPrices)) {
            return null;
        }

        return array_sum($unitPrices) / count($unitPrices);
    }

    public static function transactionCount(array $records): int
    {
        return count($records);
    }

    private static function toNumber(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric(str_replace(',', '', $value))) {
            return (float) str_replace(',', '', $value);
        }

        return null;
    }
}
