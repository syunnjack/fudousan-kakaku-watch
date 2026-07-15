@extends('layouts.app')

@section('title', config('app.name') . ' | 都道府県の不動産取引価格をLINEでウォッチ')
@section('description', '国土交通省の不動産取引価格情報をもとに、都道府県ごとの不動産価格（㎡単価）を確認できます。ウォッチ登録すると、価格が大きく変動した際にLINEで通知を受け取れます。')

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => config('app.name'),
    'url' => url('/'),
    'description' => '国土交通省の不動産取引価格情報をもとにした、都道府県ごとの不動産価格ウォッチサイト。',
    'inLanguage' => 'ja',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="container">
  <h1>都道府県の不動産取引価格をウォッチする</h1>
  <p class="text-muted">
    {{ config('app.name') }}は、国土交通省「不動産情報ライブラリ」が公開する実際の不動産取引価格データをもとに、
    都道府県ごとの不動産価格（㎡単価）を確認できるサイトです。
    気になる都道府県をウォッチ登録すると、価格が前回調査時点から大きく変動した際にLINEでお知らせします。
  </p>

  @if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
  @endif

  <form method="GET" action="{{ route('watch.search') }}" class="row g-2 mb-4">
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

  <section class="mt-5 pt-4 border-top">
    <h2 class="h5">このサイトについて</h2>
    <p class="text-muted small">
      不動産価格は公的な統計をもとにしており、口コミサイトのような利用者投稿ではありません。
      詳しいデータの取り扱いは<a href="{{ route('about') }}">こちら</a>をご覧ください。
    </p>
  </section>
</div>
@endsection
