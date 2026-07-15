<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name') . ' | 都道府県の不動産取引価格をLINEでウォッチ')</title>
    <meta name="description" content="@yield('description', '国土交通省の不動産取引価格情報をもとに、都道府県ごとの不動産価格（㎡単価）や積算法による概算家賃、実際の家賃口コミを確認できます。ウォッチ登録すると、価格や口コミの変化をLINEで通知します。')">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', config('app.name') . ' | 都道府県の不動産取引価格をLINEでウォッチ')">
    <meta property="og:description" content="@yield('description', '国土交通省の不動産取引価格情報をもとに、都道府県ごとの不動産価格（㎡単価）や積算法による概算家賃、実際の家賃口コミを確認できます。ウォッチ登録すると、価格や口コミの変化をLINEで通知します。')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="ja_JP">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="@yield('title', config('app.name') . ' | 都道府県の不動産取引価格をLINEでウォッチ')">
    <meta name="twitter:description" content="@yield('description', '国土交通省の不動産取引価格情報をもとに、都道府県ごとの不動産価格（㎡単価）や積算法による概算家賃、実際の家賃口コミを確認できます。ウォッチ登録すると、価格や口コミの変化をLINEで通知します。')">

    <link rel="icon" href="/favicon.ico" sizes="any">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      .btn-line { background: #06c755; color: #fff; border: none; }
      .btn-line:hover { background: #05a848; color: #fff; }
    </style>

    @stack('structured-data')
</head>
<body>
    <nav class="navbar navbar-dark bg-dark text-white p-3 mb-4">
        <div class="container d-flex justify-content-between align-items-center flex-wrap">
            <a href="{{ route('watch.index') }}" class="h4 mb-0 text-white text-decoration-none">{{ config('app.name') }}</a>
            <div>
                <a href="{{ route('watch.index') }}" class="text-white text-decoration-none me-3 small">価格を見る</a>
                <a href="{{ route('rent.index') }}" class="text-white text-decoration-none small">家賃口コミ</a>
            </div>
        </div>
    </nav>

    <main class="container">
        @yield('content')
    </main>

    <footer class="container text-center text-muted small py-4 mt-4 border-top">
        <a href="{{ route('about') }}" class="text-muted">このサイトについて</a>
        ／出典: <a href="https://www.reinfolib.mlit.go.jp/" class="text-muted" target="_blank" rel="noopener">国土交通省 不動産情報ライブラリ</a>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
