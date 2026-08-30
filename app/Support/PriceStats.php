<?php

namespace App\Support;

class PriceStats
{
    /**
     * 住まいに関する取引の種類。国土交通省のデータには農地・林地も混ざっており、
     * ㎡単価の桁が二つも三つも違う。相場として読みたいのはこの3種類なので、
     * 集計でも表示でも分けて扱う。
     */
    public const RESIDENTIAL_TYPES = [
        '中古マンション等',
        '宅地(土地と建物)',
        '宅地(土地)',
    ];

    /**
     * 取引価格レコード群を、種類別・市区町村別に集計する。
     *
     * 以前は種類を問わない㎡単価の単純平均だけを出していたが、これは相場を表さない。
     * 青森県の例では569件のうち287件が農地・42件が林地で、平均17,883円/㎡に対して
     * 中央値は357円/㎡（50倍差）だった。混ぜて平均した数字は、宅地の相場としても
     * 農地の相場としても読めない。
     *
     * 外れ値の影響を避けるため、代表値には中央値を使う。
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array{
     *   count: int,
     *   by_type: array<int, array{type: string, count: int, median: float}>,
     *   representative_type: string|null,
     *   representative_median: float|null,
     *   by_municipality: array<int, array{municipality: string, count: int, median: float}>
     * }
     */
    public static function summarize(array $records): array
    {
        $unitPricesByType = [];
        $unitPricesByMunicipality = [];

        foreach ($records as $record) {
            $unit = self::unitPrice($record);

            if ($unit === null) {
                continue;
            }

            $type = (string) ($record['Type'] ?? '不明');
            $unitPricesByType[$type][] = $unit;

            $municipality = trim((string) ($record['Municipality'] ?? ''));

            if ($municipality !== '') {
                $unitPricesByMunicipality[$type][$municipality][] = $unit;
            }
        }

        $byType = [];

        foreach ($unitPricesByType as $type => $values) {
            $byType[] = [
                'type' => $type,
                'count' => count($values),
                'median' => self::median($values),
            ];
        }

        // 件数の多い順。どの種類の取引が中心の地域かが分かるようにする。
        usort($byType, fn ($a, $b) => $b['count'] <=> $a['count']);

        $representativeType = self::representativeType($byType);

        $byMunicipality = [];

        if ($representativeType !== null) {
            foreach ($unitPricesByMunicipality[$representativeType] ?? [] as $municipality => $values) {
                $byMunicipality[] = [
                    'municipality' => $municipality,
                    'count' => count($values),
                    'median' => self::median($values),
                ];
            }

            usort($byMunicipality, fn ($a, $b) => $b['count'] <=> $a['count']);
        }

        $representativeMedian = null;

        foreach ($byType as $row) {
            if ($row['type'] === $representativeType) {
                $representativeMedian = $row['median'];
                break;
            }
        }

        return [
            'count' => count($records),
            'by_type' => $byType,
            'representative_type' => $representativeType,
            'representative_median' => $representativeMedian,
            'by_municipality' => $byMunicipality,
        ];
    }

    /**
     * 見出しに使う種類を選ぶ。住まいに関する3種類のうち、取引件数が最も多いもの。
     * 都市部では中古マンション、地方では宅地(土地と建物)になることが多い。
     *
     * 住宅系の取引が一件も無い県では null を返す（農地・林地しか無い場合）。
     *
     * @param  array<int, array{type: string, count: int, median: float}>  $byType
     */
    private static function representativeType(array $byType): ?string
    {
        foreach ($byType as $row) {
            if (in_array($row['type'], self::RESIDENTIAL_TYPES, true)) {
                return $row['type'];
            }
        }

        return null;
    }

    /**
     * 取引価格(TradePrice)÷面積(Area)を㎡単価として返す。
     */
    public static function unitPrice(array $record): ?float
    {
        $price = self::toNumber($record['TradePrice'] ?? null);
        $area = self::toNumber($record['Area'] ?? null);

        if ($price === null || $area === null || $area <= 0) {
            return null;
        }

        return $price / $area;
    }

    /**
     * @param  array<int, float>  $values
     */
    public static function median(array $values): ?float
    {
        if (empty($values)) {
            return null;
        }

        sort($values);
        $count = count($values);
        $middle = intdiv($count, 2);

        return $count % 2 === 1
            ? $values[$middle]
            : ($values[$middle - 1] + $values[$middle]) / 2;
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
