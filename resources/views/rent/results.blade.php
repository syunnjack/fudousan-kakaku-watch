@extends('layouts.app')

@section('title', $prefectureName . 'の家賃口コミ・相場 | ' . config('app.name'))
@section('description', $prefectureName . 'の実際の家賃口コミ一覧です。' . ($averageRentPerSqm ? '平均家賃は約' . number_format($averageRentPerSqm) . '円/㎡です。' : ''))

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => config('app.name'), 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => '家賃口コミ', 'item' => route('rent.index')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $prefectureName . 'の家賃口コミ'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="container">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('watch.index') }}">{{ config('app.name') }}</a></li>
      <li class="breadcrumb-item"><a href="{{ route('rent.index') }}">家賃口コミ</a></li>
      <li class="breadcrumb-item active" aria-current="page">{{ $prefectureName }}</li>
    </ol>
  </nav>

  <h1>{{ $prefectureName }}の家賃口コミ・相場</h1>

  @if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
  @endif

  @if($averageRentPerSqm)
    <p class="fs-5">平均家賃: <strong>約{{ number_format($averageRentPerSqm) }}円/㎡</strong>（面積の記載がある口コミより算出）</p>
  @else
    <p class="text-muted">まだ面積の記載がある口コミが少なく、㎡単価の平均は算出できていません。</p>
  @endif

  <p class="text-muted small">
    <a href="{{ route('watch.search', ['prefecture_code' => $prefectureCode]) }}">{{ $prefectureName }}の取引価格㎡単価・積算法による概算家賃はこちら</a>
  </p>

  <form method="POST" action="{{ route('rent.watches.toggle') }}" class="mb-4">
    @csrf
    <input type="hidden" name="prefecture_code" value="{{ $prefectureCode }}">
    @if ($isWatching)
      <button type="submit" class="btn btn-outline-secondary btn-sm">🔕 ウォッチをやめる</button>
    @else
      <button type="submit" class="btn btn-line btn-sm">🔔 新しい家賃口コミが投稿されたらLINEで通知</button>
    @endif
  </form>

  <section class="mb-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
      <h2 class="h5 mb-0">口コミ一覧</h2>
      <form method="GET" action="{{ route('rent.search') }}" class="d-flex align-items-center gap-2">
        <input type="hidden" name="prefecture_code" value="{{ $prefectureCode }}">
        <label for="sort" class="small text-muted mb-0">並び替え</label>
        <select id="sort" name="sort" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
          <option value="new" @selected($sort === 'new')>新着順</option>
          <option value="cheap" @selected($sort === 'cheap')>家賃が安い順</option>
          <option value="expensive" @selected($sort === 'expensive')>家賃が高い順</option>
        </select>
      </form>
    </div>
    @forelse($reports as $report)
      <div class="border rounded p-3 mb-2">
        <div class="d-flex justify-content-between">
          <strong>{{ number_format($report->rent_yen) }}円/月</strong>
          <span class="text-muted small">{{ $report->created_at->format('Y-m-d') }}</span>
        </div>
        <div class="small text-muted">
          {{ $report->city_name }}
          {{ $report->layout ? ' / ' . $report->layout : '' }}
          {{ $report->area_sqm ? ' / ' . $report->area_sqm . '㎡' : '' }}
          / {{ $report->nickname }}
        </div>
        @if($report->comment)
          <p class="mb-0 mt-1">{{ $report->comment }}</p>
        @endif
      </div>
    @empty
      <p class="text-muted">まだ口コミがありません。</p>
    @endforelse
  </section>

  <section class="mt-4 pt-4 border-top">
    <h2 class="h5">家賃口コミを投稿する</h2>
    <p class="text-muted small">実際に{{ $prefectureName }}で家賃を払っている方は、下記から投稿できます。</p>
    <form method="POST" action="{{ route('rent.reports.store') }}" class="bg-light p-3 rounded">
      @csrf
      <input type="hidden" name="prefecture_code" value="{{ $prefectureCode }}">
      <div style="position:absolute;left:-9999px;" aria-hidden="true">
        <label>ウェブサイト <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
      </div>

      <div class="row">
        <div class="col-6 mb-2">
          <label class="form-label small">市区町村（任意）</label>
          <input type="text" name="city_name" class="form-control form-control-sm" maxlength="50">
        </div>
        <div class="col-6 mb-2">
          <label class="form-label small">間取り（任意）</label>
          <input type="text" name="layout" class="form-control form-control-sm" maxlength="20" placeholder="例：1K、2LDK">
        </div>
      </div>
      <div class="row">
        <div class="col-6 mb-2">
          <label class="form-label small">専有面積㎡（任意）</label>
          <input type="number" name="area_sqm" step="0.01" class="form-control form-control-sm" min="1" max="999">
        </div>
        <div class="col-6 mb-2">
          <label class="form-label small">家賃（円） <span class="text-danger">*</span></label>
          <input type="number" name="rent_yen" class="form-control form-control-sm" min="1000" required>
        </div>
      </div>
      <div class="mb-2">
        <label class="form-label small">ニックネーム（任意）</label>
        <input type="text" name="nickname" class="form-control form-control-sm" maxlength="30">
      </div>
      <div class="mb-2">
        <label class="form-label small">コメント（任意）</label>
        <textarea name="comment" class="form-control form-control-sm" rows="3" maxlength="1000"></textarea>
      </div>
      <button type="submit" class="btn btn-dark">投稿する</button>
    </form>
  </section>
</div>
@endsection
