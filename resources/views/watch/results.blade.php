@extends('layouts.app')

@php
  $yen = fn ($v) => number_format((int) round($v));
  $repType = $latest['representative_type'] ?? null;
  $repMedian = $latest['representative_median'] ?? null;
  // 農地・林地は住まいの相場とは別物なので、表を分けて出す。
  $housing = collect($latest['by_type'] ?? [])->filter(fn ($r) => in_array($r['type'], $residentialTypes, true));
  $other = collect($latest['by_type'] ?? [])->reject(fn ($r) => in_array($r['type'], $residentialTypes, true));
@endphp

@section('title', $prefectureName . 'の不動産取引価格 | ' . config('app.name'))
@section('description', $latest
  ? $prefectureName . 'の' . $repType . 'の取引価格は㎡単価の中央値で約' . $yen($repMedian) . '円/㎡（' . $latest['year'] . '年第' . $latest['quarter'] . '四半期・' . $latest['transaction_count'] . '件）。種類別と市区町村別の内訳、直近' . count($history) . '四半期の推移を国土交通省のデータから集計しています。'
  : $prefectureName . 'の不動産取引価格を国土交通省のデータから集計しています。')

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => config('app.name'), 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $prefectureName . 'の不動産取引価格'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@if($latest)
<script type="application/ld+json">
{!! json_encode([
    '@@context' => 'https://schema.org',
    '@type' => 'Dataset',
    'name' => $prefectureName . 'の不動産取引価格（' . $latest['year'] . '年第' . $latest['quarter'] . '四半期）',
    'description' => $prefectureName . 'の不動産取引価格を、取引の種類別・市区町村別に㎡単価の中央値で集計したものです。',
    'url' => route('watch.search', ['prefecture_code' => $prefectureCode]),
    'inLanguage' => 'ja',
    'creator' => ['@type' => 'Organization', 'name' => config('app.name')],
    'isBasedOn' => [
        '@type' => 'Dataset',
        'name' => '不動産取引価格情報',
        'creator' => ['@type' => 'GovernmentOrganization', 'name' => '国土交通省'],
        'url' => 'https://www.reinfolib.mlit.go.jp/',
    ],
    'temporalCoverage' => $latest['year'] . '-Q' . $latest['quarter'],
    'spatialCoverage' => ['@type' => 'Place', 'name' => $prefectureName],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endif
@endpush

@section('content')
<div class="container">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('watch.index') }}">{{ config('app.name') }}</a></li>
      <li class="breadcrumb-item active" aria-current="page">{{ $prefectureName }}</li>
    </ol>
  </nav>

  <h1>{{ $prefectureName }}の不動産取引価格</h1>

  @if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
  @endif

  @if($latest)
    <p class="text-muted">
      国土交通省が公開している実際の取引価格を、{{ $latest['year'] }}年第{{ $latest['quarter'] }}四半期ぶん（{{ number_format($latest['transaction_count']) }}件）集計しました。
    </p>

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <p class="text-muted small mb-1">{{ $prefectureName }}で最も取引が多い種類</p>
        <p class="h4 mb-1">{{ $repType }}</p>
        <p class="fs-4 mb-1">㎡単価の中央値 <strong>約{{ $yen($repMedian) }}円/㎡</strong></p>
        @if($changeRate !== null)
          <p class="mb-0">
            前四半期（{{ $previous['year'] }}年第{{ $previous['quarter'] }}四半期）比:
            <span class="{{ $changeRate >= 0 ? 'text-danger' : 'text-primary' }}">
              {{ $changeRate >= 0 ? '+' : '' }}{{ number_format($changeRate * 100, 1) }}%
            </span>
          </p>
        @endif
      </div>
    </div>

    <section class="mb-4">
      <h2 class="h5">取引の種類別</h2>
      <p class="text-muted small">
        マンションの専有面積あたりの単価と、土地の単価は桁が違います。まとめて平均すると相場として読めない数字になるため、種類ごとに分けています。
      </p>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr><th>種類</th><th class="text-end">取引件数</th><th class="text-end">㎡単価の中央値</th></tr>
          </thead>
          <tbody>
            @foreach($housing as $row)
              <tr @class(['table-active' => $row['type'] === $repType])>
                <td>{{ $row['type'] }}</td>
                <td class="text-end">{{ number_format($row['count']) }}件</td>
                <td class="text-end">{{ $yen($row['median']) }}円/㎡</td>
              </tr>
            @endforeach
          </tbody>
          @if($other->isNotEmpty())
            <tbody class="border-top">
              <tr><th colspan="3" class="text-muted small fw-normal pt-3">住まい以外（参考）</th></tr>
              @foreach($other as $row)
                <tr class="text-muted">
                  <td>{{ $row['type'] }}</td>
                  <td class="text-end">{{ number_format($row['count']) }}件</td>
                  <td class="text-end">{{ $yen($row['median']) }}円/㎡</td>
                </tr>
              @endforeach
            </tbody>
          @endif
        </table>
      </div>
    </section>

    @if(count($history) > 1)
      <section class="mb-4">
        <h2 class="h5">{{ $repType }}の推移</h2>
        <p class="text-muted small">直近{{ count($history) }}四半期。㎡単価の中央値です。</p>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr><th>四半期</th><th class="text-end">㎡単価の中央値</th><th class="text-end">取引件数</th></tr>
            </thead>
            <tbody>
              @foreach(array_reverse($history) as $h)
                @php
                  // 種類が入れ替わった四半期は、同じ列に並べても比較にならない
                  $comparable = $h['representative_type'] === $repType;
                @endphp
                <tr>
                  <td>{{ $h['year'] }}年 第{{ $h['quarter'] }}四半期</td>
                  <td class="text-end">
                    @if($comparable)
                      {{ $yen($h['representative_median']) }}円/㎡
                    @else
                      <span class="text-muted small">{{ $h['representative_type'] }}が中心のため比較対象外</span>
                    @endif
                  </td>
                  <td class="text-end text-muted">{{ number_format($h['transaction_count']) }}件</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <p class="text-muted small">
          最新の四半期は公開の途中で、件数が確定していない場合があります。件数の増減は市況の変化とは限りません。
        </p>
      </section>
    @endif

    @if(!empty($latest['by_municipality']))
      <section class="mb-4">
        <h2 class="h5">市区町村別（{{ $repType }}）</h2>
        <p class="text-muted small">
          取引件数の多い順です。件数が少ない市区町村ほど、中央値は一部の取引に左右されます。件数もあわせてご覧ください。
        </p>
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle">
            <thead>
              <tr><th>市区町村</th><th class="text-end">取引件数</th><th class="text-end">㎡単価の中央値</th></tr>
            </thead>
            <tbody>
              @foreach($latest['by_municipality'] as $row)
                <tr>
                  <td>{{ $row['municipality'] }}</td>
                  <td class="text-end">{{ number_format($row['count']) }}件</td>
                  <td class="text-end">{{ $yen($row['median']) }}円/㎡</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </section>
    @endif

    <form method="POST" action="{{ route('watches.toggle') }}" class="mb-4">
      @csrf
      <input type="hidden" name="prefecture_code" value="{{ $prefectureCode }}">
      @if ($isWatching)
        <button type="submit" class="btn btn-outline-secondary btn-sm">🔕 ウォッチをやめる</button>
      @else
        {{-- LINEの認証情報が未設定のうちは、押すとLINE側でエラーになるので出さない --}}
        @if (config('services.line.login_channel_id'))
          <button type="submit" class="btn btn-line btn-sm">🔔 価格が大きく変動したらLINEで通知</button>
        @else
          <button type="button" class="btn btn-secondary" disabled>🔔 価格が大きく変動したらLINEで通知（準備中）</button>
        @endif
      @endif
    </form>

    @if($estimatedMonthlyRentPerSqm !== null)
      <section class="mt-4 pt-4 border-top">
        <h2 class="h5">参考家賃（積算法による概算）</h2>
        <p class="fs-5">
          約<strong>{{ $yen($estimatedMonthlyRentPerSqm) }}円/㎡/月</strong>
          <span class="text-muted small">（{{ $repType }}の中央値・想定期待利回り年{{ number_format($expectedYield * 100, 1) }}%で試算）</span>
        </p>
        <p class="text-muted small">
          不動産鑑定評価基準の積算法（基礎価格×期待利回り＋必要諸経費等）の考え方を単純化し、上記の{{ $repType }}の㎡単価を基礎価格の近似値として、
          想定期待利回り{{ number_format($expectedYield * 100, 1) }}%で試算した参考値です。
          公租公課・損害保険料・維持修繕費・空室損失相当額などの必要諸経費は含んでいないため、実際の適正賃料より低めに出る傾向があります。
          正式な鑑定評価額ではなく、あくまで概算の目安としてご利用ください。
        </p>
        <p class="text-muted small">
          実際に支払われている家賃の口コミは<a href="{{ route('rent.search', ['prefecture_code' => $prefectureCode]) }}">{{ $prefectureName }}の家賃口コミ</a>でご確認いただけます。
        </p>
      </section>
    @endif

    <section class="mt-4 pt-4 border-top">
      <h2 class="h6">この数字の読み方</h2>
      <ul class="text-muted small">
        <li>㎡単価は、取引価格（TradePrice）÷面積（Area）で算出しています。中古マンション等では専有面積、宅地では土地面積が分母です。</li>
        <li>代表値には中央値を使っています。不動産の取引価格は一部の高額な取引に強く引っ張られるため、平均だと実感から離れた数字になります。</li>
        <li>元データは実際の取引をもとにした国土交通省の公表値ですが、すべての取引が含まれるわけではありません。</li>
        <li>出典: <a href="https://www.reinfolib.mlit.go.jp/" rel="noopener" target="_blank">国土交通省 不動産情報ライブラリ</a>（不動産取引価格情報）</li>
      </ul>
    </section>
  @else
    <div class="alert alert-secondary">
      現時点で{{ $prefectureName }}の取引価格データを取得できませんでした。国土交通省のデータ公開状況によって、
      直近の四半期はまだ集計・公開されていない場合があります。
    </div>
  @endif
</div>
@endsection
