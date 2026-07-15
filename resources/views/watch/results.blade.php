@extends('layouts.app')

@section('title', $prefectureName . 'の不動産取引価格 | ' . config('app.name'))
@section('description', $prefectureName . 'の不動産取引価格（㎡単価）の最新動向です。' . ($latest ? '直近データの平均は約' . number_format((int) round($latest['avg_price_per_sqm'])) . '円/㎡です。' : ''))

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
    <p class="fs-5">
      {{ $latest['year'] }}年 第{{ $latest['quarter'] }}四半期の平均取引価格:
      <strong>約{{ number_format((int) round($latest['avg_price_per_sqm'])) }}円/㎡</strong>
      （取引件数 {{ $latest['transaction_count'] }}件）
    </p>

    @if($changeRate !== null)
      <p class="text-muted">
        前四半期（{{ $previous['year'] }}年第{{ $previous['quarter'] }}四半期）比:
        <span class="{{ $changeRate >= 0 ? 'text-danger' : 'text-primary' }}">
          {{ $changeRate >= 0 ? '+' : '' }}{{ number_format($changeRate * 100, 1) }}%
        </span>
      </p>
    @endif

    <form method="POST" action="{{ route('watches.toggle') }}" class="mb-4">
      @csrf
      <input type="hidden" name="prefecture_code" value="{{ $prefectureCode }}">
      @if ($isWatching)
        <button type="submit" class="btn btn-outline-secondary btn-sm">🔕 ウォッチをやめる</button>
      @else
        <button type="submit" class="btn btn-line btn-sm">🔔 価格が大きく変動したらLINEで通知</button>
      @endif
    </form>

    <p class="text-muted small">
      価格は取引価格（TradePrice）÷面積（Area）で算出した㎡単価の単純平均です。土地・戸建て・マンションなど取引種別を問わず集計しているため、
      あくまで大まかな相場動向の目安としてご利用ください。
    </p>
  @else
    <div class="alert alert-secondary">
      現時点で{{ $prefectureName }}の取引価格データを取得できませんでした。国土交通省のデータ公開状況によって、
      直近の四半期はまだ集計・公開されていない場合があります。
    </div>
  @endif
</div>
@endsection
