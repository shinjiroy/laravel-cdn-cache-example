<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>welcome</title>
    </head>

    <body>
        <div>トップページです</div>
        <div><a href="{{ route('mypage.index') }}">マイページへ移動</a></div>
    </body>
</html>