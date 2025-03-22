<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// レスポンスをキャッシュして良いURL
Route::withoutMiddleware([
    // set-cookieヘッダが返る物は全て除外しなければならない
    Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    Illuminate\Session\Middleware\StartSession::class,
    Illuminate\View\Middleware\ShareErrorsFromSession::class,
    Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
])->middleware([
    App\Http\Middleware\SetCacheHeaders::class
])->group(function () {
    // Controllerは省略してます
    Route::get('/', function () {
        return view('index');
    })->name('index');

    Route::get('/search', function (Request $request) {
        $q = $request->input('q');
        $hoge = $request->input('hoge');

        // ステータスコード別のレスポンス確認用
        if ($q === '404') {
            // 404を返す
            return abort(404);
        } elseif ($q === '500') {
            // 500を返す
            return abort(500);
        } elseif ($q === '302') {
            // 302を返す
            return redirect('/search?q=redirected');
        } elseif ($q === '400') {
            // 400を返す
            return abort(400);
        }

        return view('search', compact('q', 'hoge'));
    })->name('search');

    Route::get('/content/{id}', function (Request $request, $id) {
        $hoge = $request->input('hoge');
        return view('content', compact('id', 'hoge'));
    })->name('content.show');
});

// レスポンスをキャッシュしてはならないURL
Route::get('/mypage', function () {
    return view('mypage');
})->name('mypage.index');
