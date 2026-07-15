@extends('layouts.app')

@section('title', '家賃口コミ | ' . config('app.name'))
@section('description', '実際に住んでいる人が投稿する家賃口コミから、都道府県ごとの家賃相場を確認できます。ウォッチ登録すると、新しい口コミが投稿された際にLINEで通知を受け取れます。')

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => '家賃口コミ | ' . config('app.name'),
    'url' => url('/rent'),
    'description' => '実際に住んでいる人が投稿する家賃口コミによる都道府県別の家賃相場サイト。',
    'inLanguage' => 'ja',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="container">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('watch.index') }}">{{ config('app.name') }}</a></li>
      <li class="breadcrumb-item active" aria-current="page">家賃口コミ</li>
    </ol>
  </nav>

  <h1>家賃口コミで相場を調べる</h1>
  <p class="text-muted">
    実際にその都道府県に住んでいる方が投稿する「実際に払っている家賃」の口コミから、家賃相場を確認できます。
    トップページの取引価格㎡単価から算出する「積算法による概算」とあわせてご覧いただくと、より実態に近い相場感がつかめます。
  </p>

  @if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
  @endif

  <form method="GET" action="{{ route('rent.search') }}" class="row g-2 mb-4">
    <div class="col-9 col-md-6">
      <select name="prefecture_code" class="form-select" required>
        <option value="">都道府県を選択</option>
        @foreach($prefectures as $code => $name)
          <option value="{{ $code }}">{{ $name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-3 col-md-2">
      <button type="submit" class="btn btn-primary w-100">確認する</button>
    </div>
  </form>

  @if($recentPrefectures->isNotEmpty())
    <h2 class="h5">口コミの多い都道府県</h2>
    <div class="row row-cols-2 row-cols-md-4 g-2 mt-1 mb-4">
      @foreach($recentPrefectures as $p)
        <div class="col">
          <a href="{{ route('rent.search', ['prefecture_code' => $p->prefecture_code]) }}" class="btn btn-outline-primary w-100">
            {{ $p->prefecture_name }}（{{ $p->reports_count }}件）
          </a>
        </div>
      @endforeach
    </div>
  @endif
</div>
@endsection
