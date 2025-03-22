<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>welcome</title>
    </head>

    <body>
        <div>検索ページです</div>
        <div>q: {{ $q }}</div>
        <div>hoge: {{ $hoge }}</div>

        <form action="{{ route('search') }}" method="get">
            <input type="text" name="q" value="{{ $q }}">
            <input type="text" name="hoge" value="{{ $hoge }}">
            <button type="submit">検索</button>
        </form>
    </body>
</html>
