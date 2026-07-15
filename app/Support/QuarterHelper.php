<?php

namespace App\Support;

use Carbon\Carbon;

class QuarterHelper
{
    /**
     * 不動産情報ライブラリの取引価格データは四半期ごとに、実際の取引から
     * ある程度遅れて公開されるため、直近2四半期分は未公開である前提で
     * 「参照を試す最新の四半期」を計算する。
     *
     * @return array{0: int, 1: int} [year, quarter]
     */
    public static function latestReportableQuarter(?Carbon $now = null): array
    {
        $now = $now ?? now();
        $year = (int) $now->year;
        $quarter = (int) ceil($now->month / 3);

        return self::subtractQuarters($year, $quarter, 2);
    }

    /**
     * 最新の参照候補から遡って $count 件の [year, quarter] を返す。
     * データが存在しない四半期があるため、複数候補を順に試す用途。
     *
     * @return array<int, array{0: int, 1: int}>
     */
    public static function candidateQuarters(int $count = 4, ?Carbon $now = null): array
    {
        [$year, $quarter] = self::latestReportableQuarter($now);

        $candidates = [];
        for ($i = 0; $i < $count; $i++) {
            $candidates[] = self::subtractQuarters($year, $quarter, $i);
        }

        return $candidates;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function subtractQuarters(int $year, int $quarter, int $steps): array
    {
        $totalQuarters = ($year * 4 + ($quarter - 1)) - $steps;
        $year = intdiv($totalQuarters, 4);
        $quarter = ($totalQuarters % 4) + 1;

        return [$year, $quarter];
    }
}
