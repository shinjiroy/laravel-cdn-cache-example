<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>welcome</title>
    </head>

    <body>
        <div>テストページです</div>
        <div>q: {{ $q }}</div>
        <div>hoge: {{ $hoge }}</div>

        <form action="{{ route('test') }}" method="get">
            <label for="q">q</label>
            <input type="text" name="q" value="{{ $q }}">
            <label for="hoge">hoge</label>
            <input type="text" name="hoge" value="{{ $hoge }}">
            <button type="submit">Submit</button>
        </form>
    </body>
</html>
