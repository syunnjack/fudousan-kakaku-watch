# fudousan-kakaku-watch

fudousan-kakaku-watch は、国土交通省の不動産取引価格情報をもとに、都道府県ごとの売買価格動向と参考家賃を確認し、LINE 通知でウォッチできる Laravel アプリケーションです。

## 主な機能

- 都道府県ごとの不動産取引価格（平均㎡単価）の表示
- 前四半期比の変動確認
- 想定期待利回りからの参考家賃試算
- LINE ログインを使ったウォッチ登録
- 家賃口コミの収集と新着通知
- sitemap.xml の配信

## 開発手順

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan serve
npm run dev
```

## 品質確認

```bash
php artisan test
npm run build
```

## 補足

- 売買価格データは国土交通省「不動産情報ライブラリ」の API を利用します。
- 通知機能の利用には LINE 連携用の環境変数設定が必要です。
