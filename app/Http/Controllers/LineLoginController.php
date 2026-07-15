<?php

namespace App\Http\Controllers;

use App\Models\AreaWatch;
use App\Models\LineUser;
use App\Models\RentWatch;
use App\Support\LineMessaging;
use App\Support\Prefectures;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LineLoginController extends Controller
{
    public function redirect(Request $request)
    {
        $state = Str::random(40);
        $request->session()->put('line_login_state', $state);

        if ($request->filled('prefecture_code')) {
            $request->session()->put('line_login_intended_prefecture_code', $request->input('prefecture_code'));
        }

        if ($request->filled('rent_prefecture_code')) {
            $request->session()->put('line_login_intended_rent_prefecture_code', $request->input('rent_prefecture_code'));
        }

        return redirect()->away(LineMessaging::authorizeUrl($state));
    }

    public function callback(Request $request)
    {
        $state = $request->query('state');
        $expectedState = $request->session()->pull('line_login_state');

        if (! $state || $state !== $expectedState) {
            return redirect()->route('watch.index')->withErrors(['line' => 'LINEログインの検証に失敗しました。もう一度お試しください。']);
        }

        if (! $request->filled('code')) {
            return redirect()->route('watch.index')->withErrors(['line' => 'LINEログインがキャンセルされました。']);
        }

        $token = LineMessaging::exchangeToken($request->input('code'));
        $claims = LineMessaging::verifyIdToken($token['id_token']);

        $lineUser = LineUser::updateOrCreate(
            ['line_user_id' => $claims['sub']],
            ['display_name' => $claims['name'] ?? null]
        );

        $request->session()->put('line_user_local_id', $lineUser->id);

        $intendedPrefectureCode = $request->session()->pull('line_login_intended_prefecture_code');
        $prefectureName = $intendedPrefectureCode ? Prefectures::name($intendedPrefectureCode) : null;

        if ($intendedPrefectureCode && $prefectureName) {
            AreaWatch::firstOrCreate(
                ['line_user_id' => $lineUser->id, 'prefecture_code' => $intendedPrefectureCode],
                ['prefecture_name' => $prefectureName, 'last_checked_at' => now()]
            );

            return redirect()->route('watch.search', ['prefecture_code' => $intendedPrefectureCode])
                ->with('success', 'ウォッチ登録が完了しました。価格が大きく変動するとLINEでお知らせします。');
        }

        $intendedRentPrefectureCode = $request->session()->pull('line_login_intended_rent_prefecture_code');
        $rentPrefectureName = $intendedRentPrefectureCode ? Prefectures::name($intendedRentPrefectureCode) : null;

        if ($intendedRentPrefectureCode && $rentPrefectureName) {
            RentWatch::firstOrCreate(
                ['line_user_id' => $lineUser->id, 'prefecture_code' => $intendedRentPrefectureCode],
                ['prefecture_name' => $rentPrefectureName, 'last_checked_at' => now()]
            );

            return redirect()->route('rent.search', ['prefecture_code' => $intendedRentPrefectureCode])
                ->with('success', 'ウォッチ登録が完了しました。新しい家賃口コミが投稿されるとLINEでお知らせします。');
        }

        return redirect()->route('watch.index')->with('success', 'LINEログインが完了しました。');
    }
}
