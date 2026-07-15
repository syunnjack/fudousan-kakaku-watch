<?php

namespace App\Http\Controllers;

use App\Models\AreaWatch;
use App\Support\Prefectures;
use Illuminate\Http\Request;

class WatchController extends Controller
{
    public function toggle(Request $request)
    {
        $validated = $request->validate([
            'prefecture_code' => 'required|string|size:2',
        ]);
        $prefectureCode = $validated['prefecture_code'];
        $prefectureName = Prefectures::name($prefectureCode);

        if (! $prefectureName) {
            return back()->withErrors(['prefecture_code' => '都道府県が見つかりませんでした。']);
        }

        $lineUserLocalId = $request->session()->get('line_user_local_id');

        if (! $lineUserLocalId) {
            return redirect()->route('line.login', ['prefecture_code' => $prefectureCode]);
        }

        $watch = AreaWatch::where('line_user_id', $lineUserLocalId)
            ->where('prefecture_code', $prefectureCode)
            ->first();

        if ($watch) {
            $watch->delete();

            return back()->with('success', 'ウォッチを解除しました。');
        }

        AreaWatch::create([
            'line_user_id' => $lineUserLocalId,
            'prefecture_code' => $prefectureCode,
            'prefecture_name' => $prefectureName,
            'last_checked_at' => now(),
        ]);

        return back()->with('success', '価格が大きく変動したらLINEでお知らせします。');
    }
}
