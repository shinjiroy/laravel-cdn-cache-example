<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>welcome</title>
    </head>

    <body>
        <div>コンテンツページです</div>
        <div>id: {{ $id }}</div>
        <div>hoge: {{ $hoge }}</div>

        <div>
            <label for="id">id</label>
            <input id="id" type="text" name="id" value="{{ $id }}">
            <label for="hoge">hoge</label>
            <input id="hoge" type="text" name="hoge" value="{{ $hoge }}">
            <button id="update" type="submit">更新</button>
        </div>
    </body>
    <script>
        document.getElementById('update').addEventListener('click', function() {
            const id = document.getElementById('id').value;
            const hoge = document.getElementById('hoge').value;
            const newId = id;
            const newHoge = hoge;
            window.location.href = '/content/' + newId + '?hoge=' + newHoge;
        });
    </script>
</html>
