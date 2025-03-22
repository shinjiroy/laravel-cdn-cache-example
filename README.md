# laravel-cdn-cache-example

オリジンサーバーのLaravelで生成した動的コンテンツのレスポンスをCDNでキャッシュする場合の実装例です。

- ブラウザキャッシュのための`Cache-Control`
- CDNキャッシュのための`Cdn-Cache-Control`
- キャッシュ削除をするための`Cache-Tag`

を設定することを想定しています。  
また、CDNはCloudflare CDNを想定していますが、他のCDNでも要点は同じです。

見るべきファイルは以下です。

- [src/routes/web.php](src/routes/web.php)
  - キャッシュして良いURLとしてはならないURLでの、Middlewareの適用の仕方が分かります。
- [src/app/Http/Middleware/SetCacheHeaders.php](src/app/Http/Middleware/SetCacheHeaders.php)
  - キャッシュして良いURLに適用しているMiddlewareです。
- [src/app/Helpers/CacheHeaderHelper.php](src/app/Helpers/CacheHeaderHelper.php)
  - SetCacheHeadersで使われる処理です。ここが肝です。
  - [テストコード](src/tests/Unit/Helpers/CacheHeaderHelperTest.php)も合わせて見てください。
- [src/config/cache-headers.php](src/config/cache-headers.php)
  - キャッシュして良いURLのキャッシュ設定を書く場所です。

## 確認

セットアップ

```sh
docker compose up -d
docker compose exec app composer install
```

`http://localhost/`にアクセス

### ブラウザからの確認

各画面を開き、それぞれのレスポンスヘッダ

- `Cache-Control`
- `Cdn-Cache-Control`
- `Cache-Tag`

を確認してください。  
特に`Cache-Tag`はパスパラメータや特定のクエリパラメータが反映されていることを確認してください。
