@extends('layouts.app')

@section('title', 'このサイトについて | ' . config('app.name'))
@section('description', config('app.name') . 'の運営方針、データの出典、LINE通知の仕組みについて説明しています。')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('watch.index') }}">{{ config('app.name') }}</a></li>
      <li class="breadcrumb-item active" aria-current="page">このサイトについて</li>
    </ol>
  </nav>

  <h1>このサイトについて</h1>

  <section class="mb-4">
    <h2 class="h5">サイトの目的</h2>
    <p>
      「{{ config('app.name') }}」は、国土交通省「不動産情報ライブラリ」が公開する実際の不動産取引価格情報をもとに、
      都道府県ごとの不動産価格（㎡単価）の動向を確認できるサイトです。都道府県をウォッチ登録すると、
      価格が前回調査時点から大きく変動した際にLINE公式アカウントからお知らせします。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h5">データの出典について</h2>
    <p>
      掲載している価格データは、国土交通省「不動産情報ライブラリ」（<a href="https://www.reinfolib.mlit.go.jp/" target="_blank" rel="noopener">https://www.reinfolib.mlit.go.jp/</a>）が
      公開する不動産取引価格情報APIを利用しています。データは四半期ごとに集計・公開されるものであり、
      実際の取引からある程度の期間を経て公開されるため、直近1〜2四半期分はまだ反映されていない場合があります。
      当サイトはこのデータを二次利用して都道府県単位の平均㎡単価を算出・表示しているものであり、国土交通省による見解を示すものではありません。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h5">対象範囲について</h2>
    <p>
      現時点では都道府県単位の不動産取引価格（売買価格）のみを対象としています。市区町村単位での絞り込みや、
      家賃相場の変動通知については、適切な公開データソースが確認でき次第、対応を検討します。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h5">LINE通知について</h2>
    <p>
      各都道府県のページから「🔔 価格が大きく変動したらLINEで通知」を選ぶと、LINEログインのうえその都道府県をウォッチ登録できます。
      登録した都道府県の平均㎡単価が、前回確認時点から10%以上変動すると、LINE公式アカウントからお知らせします。
    </p>
  </section>
</div>
@endsection
