<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Config;

/**
 * キャッシュ関連のヘッダを生成するヘルパークラスです。
 * ※とりあえずCloudflare CDNの仕様に合わせています。
 */
class CacheHeaderHelper
{
    /**
     * キャッシュタグを生成します
     * 
     * キャッシュタグのフォーマットは{key}:{value}です。
     * ただし、パラメータのキーに表記ゆれがある場合は$valueのみが良いです。
     *
     * @param Request $request
     * @return string
     */
    private static function generateCacheTags(Request $request): string
    {
        $tags = [];

        // ルート名をタグに追加
        $route_name = $request->route()->getName();
        $tags = $route_name ? self::generateTagsByRouteName($route_name) : [];

        // パスパラメータをタグに追加
        foreach ($request->route()->parameters() as $key => $value) {
            if (!self::isValidCacheTag($value)) {
                continue;
            }
            $tags[] = Str::lower("{$key}:{$value}");
        }

        // 設定されたクエリパラメータをタグに追加
        $routeConfig = self::getRouteConfig($request);
        if ($routeConfig && isset($routeConfig['query_params'])) {
            foreach ($routeConfig['query_params'] as $key) {
                if ($request->has($key)) {
                    if (!self::isValidCacheTag($request->query($key))) {
                        continue;
                    }
                    $tags[] = Str::lower("{$key}:{$request->query($key)}");
                }
            }
        }

        // キャッシュタグの重複を削除
        $tags = array_unique($tags);

        $result = [];
        $length = 0;
        foreach ($tags as $tag) {
            // 16 * 1024文字を超えたらそれ以降は無視する
            if ($length + strlen($tag) > 16 * 1024) {
                break;
            }

            $result[] = $tag;
            $length += strlen($tag);
        }

        return implode(',', $result);
    }

    /**
     * ルート設定を取得します
     *
     * @param Request $request
     * @return array|null
     */
    private static function getRouteConfig(Request $request): ?array
    {
        $route = $request->route();
        if (!$route) {
            return null;
        }

        $uri = $request->route()->uri();
        return Config::get('cache-headers.routes.' . $uri);
    }

    /**
     * キャッシュヘッダーを設定します
     *
     * @param Request $request
     * @return array
     */
    public static function generateCacheHeaders(Request $request): array
    {
        $headers = [];
        $defaults = Config::get('cache-headers.defaults');
        $routeConfig = self::getRouteConfig($request);

        // Cache-Tagヘッダーの設定
        $tags = self::generateCacheTags($request);
        if ($tags) {
            $headers['Cache-Tag'] = $tags;
        }

        // ブラウザキャッシュ用のCache-Control
        // ブラウザキャッシュのために使うのでprivateをつける。また、キチンと付けないとLaravelによって何かしら自動的につく。
        $headers['Cache-Control'] = "max-age={$defaults['browser_max_age']}, private";

        // CDNキャッシュ用のCdn-Cache-Control
        $cdnMaxAge = $routeConfig['cdn_max_age'] ?? $defaults['browser_max_age'];
        $headers['Cdn-Cache-Control'] = "max-age={$cdnMaxAge}, stale-while-revalidate={$defaults['cdn_stale_while_revalidate']}";

        return $headers;
    }

    /**
     * 例えば
     * 'hoge.fuga.poyo'から
     * ['hoge', 'hoge.fuga', 'hoge.fuga.poyo']
     * のように階層的に文字列を切り出して配列化する
     * 
     * Note: 下の階層を纏めて削除出来るようにするための細工だが、システムによっては不要かもしれません。
     *
     * @param string $input
     * @return array
     */
    private static function generateTagsByRouteName(string $input) : array
    {
        $segments = explode('.', $input);
        $result = [];

        $tmp = '';
        foreach ($segments as $segment) {
            $tmp .= $segment;
            $result[] = $tmp;
            $tmp .= '.';
        }

        return $result;
    }

    /**
     * CDNでキャッシュして良いレスポンスを定義する
     *
     * @param Request $request
     * @param Response $response
     * @return boolean
     */
    public static function isCachableResponse(Request $request, Response $response) : bool
    {
        // キャッシュ設定が無ければキャッシュしない
        if (empty(self::getRouteConfig($request))) {
            return false;
        }

        // Status Code
        if ($response->isSuccessful()) {
            return true;
        }

        // リダイレクトはキャッシュして良い(要件次第)
        if ($response->isRedirection()) {
            return true;
        }

        // 404はキャッシュして良い
        if ($response->isNotFound()) {
            return true;
        }

        return false;
    }

    /**
     * CacheTagに指定して良い文字列
     * 
     * Note:
     * このメソッドでは簡単なチェックしかしていません。
     * 何かしら攻撃の意図のあるリクエストは各URLの処理で正しくバリデーションされ、4XXエラーが返る想定です。
     *
     * @param mixed $tag
     * @return boolean
     */
    private static function isValidCacheTag($tag): bool
    {
        if (!is_scalar($tag) || empty($tag)) {
            return false;
        }

        $tag = (string)$tag;

        // キャッシュタグはASCII文字のみである必要がある
        // スペースは使用できない
        return preg_match('/^[\x21-\x7E]+$/', $tag) === 1;
    }
}
