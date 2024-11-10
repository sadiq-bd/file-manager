<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ env('APP_NAME', 'File Manager') }}</title>

  <style>
    * {
      background: #000;
      color: #fff;
    }

    html, body {
      margin: 0;
      padding: 0;
    }

    .container {
      text-align: center;
      margin: 10px;
      padding: 15px;
      border: 1px solid #666;
      overflow-x: auto;
    }

    button {
      border: 1px solid #eef;
    }

    .bigBtn {
      font-size: 15px;
      padding: 10px;
    }

    input, textarea {
      border: 1px solid #eef;
      padding: 8px;
    }

  </style>
</head>
<body>

<main>
  <div class="container">
    <button onclick="window.location = '{!! url('/') . "/?dir=$currentDir" !!}'">Back</button><br><br>
    <h3>Edit {{ $file->fileName }}</h3>
    <p>
<pre>
Size: {{ _format_size($file->fileSize) }}
Type: {{ $file->fileMimeType }}
Owner: {{ $file->fileOwner }} Group: {{ $file->fileGroup }}
Permissions: {{ $file->filePermissions }}
Last Modified: {{ date('d M y H:i:s', $file->fileModificationTime) }}
</pre>
    </p>
    <form action="/editFile?_token_={!! urlencode(_token_generate()) !!}" method="post">
        @csrf
        <input type="hidden" name="dir" value="{!! $currentDir !!}" autocomplete="off">
        <input type="hidden" name="file" value="{!! $file->fileName !!}" autocomplete="off">
        <input type="text" name="name" style="width:calc(100% - 20px);" value="{!! $file->fileName !!}"><br><br>
        <textarea name="content" id="" style="width:calc(100% - 20px);" rows="15">{{ $file->content }}</textarea><br><br>
        <button class="bigBtn">Save</button>
    </form>
  </div>
</main>

</body>
</html>
