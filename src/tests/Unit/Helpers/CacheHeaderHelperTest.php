<?php

namespace Tests\Unit\Helpers;

use App\Helpers\CacheHeaderHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CacheHeaderHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // テスト用の設定を読み込む
        Config::set('cache-headers', [
            'routes' => [
                '/' => [
                    'query_params' => ['test'],
                    'cdn_max_age' => 3600,
                ],
                'api/products/{id}' => [
                    'query_params' => ['category', 'brand'],
                    'cdn_max_age' => 3600,
                ],
                'api/articles/{id}' => [
                    'query_params' => ['tag'],
                    'cdn_max_age' => 1800,
                ],
            ],
            'defaults' => [
                'browser_max_age' => 60,
                'cdn_stale_while_revalidate' => 3600,
            ],
        ]);
    }

    /**
     * privateメソッドを呼び出すためのヘルパーメソッド
     */
    private function invokePrivateMethod(string $methodName, array $args = []): mixed
    {
        $reflection = new ReflectionClass(CacheHeaderHelper::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs(null, $args);
    }

    public function test_ルートURIがスラッシュの場合のキャッシュタグが生成される()
    {
        $request = Request::create('/', 'GET', [
            'test' => 'testvalue',
        ]);
        $request->setRouteResolver(function () use ($request) {
            return (new \Illuminate\Routing\Route('GET', '/', []))
                ->name('home')
                ->bind($request);
        });

        $tags = $this->invokePrivateMethod('generateCacheTags', [$request]);
        $this->assertEquals('home,test:testvalue', $tags);
    }

    public function test_パスパラメータからキャッシュタグが生成される()
    {
        $request = Request::create('/api/products/123', 'GET');
        $request->setRouteResolver(function () use ($request) {
            return (new \Illuminate\Routing\Route('GET', '/api/products/{id}', []))
                ->name('products.show')
                ->bind($request);
        });

        $tags = $this->invokePrivateMethod('generateCacheTags', [$request]);
        $this->assertEquals('products,products.show,id:123', $tags);
    }

    public function test_クエリパラメータからキャッシュタグが生成される()
    {
        $request = Request::create('/api/products/123', 'GET', [
            'category' => 'electronics',
            'brand' => 'samsung'
        ]);
        $request->setRouteResolver(function () use ($request) {
            return (new \Illuminate\Routing\Route('GET', '/api/products/{id}', []))
                ->name('products.show')
                ->bind($request);
        });

        $tags = $this->invokePrivateMethod('generateCacheTags', [$request]);
        $this->assertEquals('products,products.show,id:123,category:electronics,brand:samsung', $tags);
    }

    public function test_設定されていないクエリパラメータはキャッシュタグに含まれない()
    {
        $request = Request::create('/api/products/123', 'GET', [
            'category' => 'electronics',
            'color' => 'red'
        ]);
        $request->setRouteResolver(function () use ($request) {
            return (new \Illuminate\Routing\Route('GET', '/api/products/{id}', []))
                ->name('products.show')
                ->bind($request);
        });

        $tags = $this->invokePrivateMethod('generateCacheTags', [$request]);
        $this->assertEquals('products,products.show,id:123,category:electronics', $tags);
    }

    public function test_キャッシュヘッダーが正しく生成される()
    {
        $request = Request::create('/api/products/123', 'GET');
        $request->setRouteResolver(function () use ($request) {
            return (new \Illuminate\Routing\Route('GET', '/api/products/{id}', []))
                ->name('products.show')
                ->bind($request);
        });

        $headers = CacheHeaderHelper::generateCacheHeaders($request);

        $this->assertArrayHasKey('Cache-Tag', $headers);
        $this->assertArrayHasKey('Cache-Control', $headers);
        $this->assertArrayHasKey('Cdn-Cache-Control', $headers);

        $this->assertEquals('products,products.show,id:123', $headers['Cache-Tag']);
        $this->assertEquals('max-age=60, private', $headers['Cache-Control']);
        $this->assertEquals('max-age=3600, stale-while-revalidate=3600', $headers['Cdn-Cache-Control']);
    }

    public function test_レスポンスがキャッシュ可能かどうかを判定できる()
    {
        $request = Request::create('/api/products/123', 'GET');
        $request->setRouteResolver(function () use ($request) {
            return (new \Illuminate\Routing\Route('GET', '/api/products/{id}', []))
                ->name('products.show')
                ->bind($request);
        });

        // 200 OKのレスポンス
        $response = response()->json(['data' => 'test']);
        $this->assertTrue(CacheHeaderHelper::isCachableResponse($request, $response));

        // 404 Not Foundのレスポンス
        $response = response()->json(['error' => 'Not found'], 404);
        $this->assertTrue(CacheHeaderHelper::isCachableResponse($request, $response));

        // 500 Internal Server Errorのレスポンス
        $response = response()->json(['error' => 'Server error'], 500);
        $this->assertFalse(CacheHeaderHelper::isCachableResponse($request, $response));
    }

    public function test_abort404のレスポンスがキャッシュ可能と判定される()
    {
        $request = Request::create('/api/products/123', 'GET');
        $request->setRouteResolver(function () use ($request) {
            return (new \Illuminate\Routing\Route('GET', '/api/products/{id}', []))
                ->name('products.show')
                ->bind($request);
        });

        // abort(404)のレスポンスをシミュレート
        $response = response()->json(['error' => 'Not found'], 404);

        $this->assertTrue(CacheHeaderHelper::isCachableResponse($request, $response));
    }
}
