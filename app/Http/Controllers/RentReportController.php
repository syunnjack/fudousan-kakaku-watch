<?php

namespace App\Http\Controllers;

use App\Models\RentReport;
use App\Models\RentWatch;
use App\Support\ContentModeration;
use App\Support\Prefectures;
use Illuminate\Http\Request;

class RentReportController extends Controller
{
    public function index()
    {
        $prefectures = Prefectures::all();

        $recentPrefectures = RentReport::select('prefecture_code', 'prefecture_name')
            ->selectRaw('COUNT(*) as reports_count')
            ->groupBy('prefecture_code', 'prefecture_name')
            ->orderByDesc('reports_count')
            ->take(12)
            ->get();

        return view('rent.index', compact('prefectures', 'recentPrefectures'));
    }

    private const SORT_OPTIONS = ['new', 'cheap', 'expensive'];

    public function search(Request $request)
    {
        $validated = $request->validate([
            'prefecture_code' => 'required|string|size:2',
        ]);
        $prefectureCode = $validated['prefecture_code'];
        $prefectureName = Prefectures::name($prefectureCode);

        if (! $prefectureName) {
            return redirect()->route('rent.index')->withErrors(['prefecture_code' => '都道府県が見つかりませんでした。']);
        }

        $sort = in_array($request->query('sort'), self::SORT_OPTIONS, true) ? $request->query('sort') : 'new';

        $query = RentReport::where('prefecture_code', $prefectureCode);
        $reports = match ($sort) {
            'cheap' => $query->orderBy('rent_yen')->get(),
            'expensive' => $query->orderByDesc('rent_yen')->get(),
            default => $query->latest()->get(),
        };

        $unitPrices = $reports
            ->filter(fn ($r) => $r->area_sqm && $r->area_sqm > 0)
            ->map(fn ($r) => $r->rent_yen / $r->area_sqm);
        $averageRentPerSqm = $unitPrices->isNotEmpty() ? (int) round($unitPrices->avg()) : null;

        $isWatching = session('line_user_local_id')
            ? RentWatch::where('line_user_id', session('line_user_local_id'))
                ->where('prefecture_code', $prefectureCode)
                ->exists()
            : false;

        return view('rent.results', [
            'prefectureCode' => $prefectureCode,
            'prefectureName' => $prefectureName,
            'reports' => $reports,
            'averageRentPerSqm' => $averageRentPerSqm,
            'isWatching' => $isWatching,
            'sort' => $sort,
        ]);
    }

    public function store(Request $request)
    {
        if ($request->filled('website')) {
            return back()->with('success', '投稿を受け付けました。');
        }

        $validated = $request->validate([
            'prefecture_code' => 'required|string|size:2',
            'city_name' => 'nullable|string|max:50',
            'layout' => 'nullable|string|max:20',
            'area_sqm' => 'nullable|numeric|min:1|max:999',
            'rent_yen' => 'required|integer|min:1000|max:10000000',
            'nickname' => 'nullable|string|max:30',
            'comment' => 'nullable|string|max:1000',
        ]);

        $prefectureName = Prefectures::name($validated['prefecture_code']);

        if (! $prefectureName) {
            return back()->withErrors(['prefecture_code' => '都道府県が見つかりませんでした。']);
        }

        if (! empty($validated['comment']) && ContentModeration::containsNgWord($validated['comment'])) {
            return back()->withErrors(['comment' => '不適切な内容が含まれている可能性があります。']);
        }

        $ipHash = ContentModeration::clientIpHash($request);
        if (ContentModeration::isTooSoon("rent-report:{$ipHash}", 30)) {
            return back()->withErrors(['rent_yen' => '連続投稿はできません。しばらくしてから再度お試しください。']);
        }

        RentReport::create([
            'prefecture_code' => $validated['prefecture_code'],
            'prefecture_name' => $prefectureName,
            'city_name' => $validated['city_name'] ?? null,
            'layout' => $validated['layout'] ?? null,
            'area_sqm' => $validated['area_sqm'] ?? null,
            'rent_yen' => $validated['rent_yen'],
            'nickname' => $validated['nickname'] ?: '匿名',
            'comment' => $validated['comment'] ?? null,
            'ip_hash' => $ipHash,
        ]);

        return redirect()->route('rent.search', ['prefecture_code' => $validated['prefecture_code']])
            ->with('success', '家賃口コミを投稿しました。ありがとうございます。');
    }
}
