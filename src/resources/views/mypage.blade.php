<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>mypage</title>
    </head>

    <body>
        <div>マイページです</div>
        <div>
            @auth
                ログイン済み
            @else
                未ログイン
            @endauth
        </div>
    </body>
</html>
