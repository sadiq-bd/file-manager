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

    .tbl-container {
      text-align: center;
      overflow-x: auto;
    }

    button {
      border: 1px solid #eef;
    }

    .bigBtn {
      font-size: 15px;
      padding: 10px;
    }

    table {
      width: 100%;
    }

    td > a {
      text-decoration: none;
    }
    td > button {
      margin: 4px;
    }
    button:hover {
      color: #000;
      background: #eef;
    }
    .banner {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      text-align: center;
      max-height: 40px;
      padding: 8px;
    }

    @media (max-width: 768px) {
      tr td > * {
        font-size: 14px;
      }
    }
  </style>
</head>
<body>

<main>
  <div class="container">
  @if(count($fileList) > 0) 
    <button onclick="createItem('file');" class="bigBtn">Create New File</button>&nbsp;&nbsp;
    <button onclick="createItem('dir');" class="bigBtn">Create New Folder</button><br><br>
    <form id="fileUploader" style="border: 1px solid #666; padding: 10px;" action="{!!
      route('uploadFile', [
              'dir' => $currentDir, 
              '_token_' => _token_generate()
      ]) 
    !!}" method="post" enctype="multipart/form-data">
      @csrf
      <input type="file" name="file[]" class="bigBtn" id="fileInput" required multiple>
      <button type="submit" class="bigBtn" id="uploadBtn">Upload</button>
    </form>

    {{-- Nested Directory --}}
    <p style="font-size: 17px;">
    <a style="text-decoration:none;" href="{!! route('index', ['dir'=>'']) !!}">root</a>
    @php
        $cdirFinal = '';
    @endphp
    @foreach (explode('/', trim($currentDir, '/')) as $cdir)
      @php
        $cdirFinal .= '/' . $cdir;
      @endphp
      /&nbsp;<a style="text-decoration:none;" href="{!! route('index', ['dir'=>$cdirFinal]) !!}">{{ $cdir }}</a>
    @endforeach
    </p>

    <div class="tbl-container">
    <table border="1" cellspacing="0" cellpadding="10">
      <thead>
        <tr>
          <td onclick="revertFilesSelection()"></td>
          <td>File Name</td>
          <td>File Size</td>
          <td>File Type</td>
          <td>Last Modified</td>
          <td>Actions</td>
        </tr>
      </thead>
      <tbody>
        @php 
          $totalSizeOfFiles = 0;
        @endphp
        @foreach($fileList as $index => $file)
        <tr>
          @if ($file->fileName == '.')
            @if ($currentDir != '' && $currentDir != '.')
            <td></td>
            <td><a href="" style="font-size: 24px; padding: 8px;">.</a></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            @endif
          @elseif ($file->fileName == '..')
            @if ($currentDir != '' && $currentDir != '.' && $currentDir != '..')
            <td></td>
            <td><a style="font-size: 24px; padding: 8px;" href="{!! route('index', ['dir' => pathinfo($currentDir, PATHINFO_DIRNAME)]) !!}">..</a></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            @endif
          @else
          <td><input type="checkbox" name="fileSelect" value="{!! $file->fileName !!}"></td>
          <td><a href="{!! 
            $file->isDir ?
            route('index', [
              'dir' => $currentDir . '/' . $file->fileName
            ]) :
            route('viewFile', [
              'dir' => $currentDir, 
              'file' => $file->fileName,
              '_token_' => _token_generate()
            ]) 
          !!}" {!! $file->isDir ? '' : 'target="_blank"' !!}>{!! $file->isDir ? '/&nbsp;' : '' !!}{{ $file->fileName }}{!! $file->isDir ? '&nbsp;/' : '' !!}</a></td>
          <td>{{ $file->isDir ? '' : _format_size($file->fileSize) }}</td>
          <td>{{ $file->fileMimeType }}</td>
          <td>{{ date('d M y H:i:s', $file->fileModificationTime) }}</td>
          <td>
            <button onclick="renameFile('{!! $file->fileName !!}')">rename</button>
            @if (!$file->isDir)
            <button onclick="window.location = '{!! 
              route('editFile', [
                'dir' => $currentDir, 
                'file' => $file->fileName,
                '_token_' => _token_generate()
              ]) 
            !!}'">edit</button>
            <button onclick="window.location = '{!!
              route('downloadFile', [
                'dir' => $currentDir,
                'file' => $file->fileName,
                '_token_' => _token_generate()
              ])
            !!}';">download</button>
            @endif
            <button onclick="if (confirm('Are you sure?')) { window.location = '{!! 
            route('deleteFile', [
              'dir' => $currentDir, 
              'file' => $file->fileName,
              '_token_' => _token_generate()
            ]) 
            !!}'; } ">delete</button>
          </td>
            @php
              if (!$file->isDir) { 
                $totalSizeOfFiles += $file->fileSize;
              }
            @endphp
          @endif
        </tr>
        @endforeach
        <tr>
          <td></td>
          <td><b style="color: #888;">Total Size of Files in {{ $currentDir == '.' || $currentDir == '' ? '/' : $currentDir }}</b></td>
          <td><b style="color: #888;">{{ _format_size($totalSizeOfFiles) }}</b></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
      </tbody>
    </table>
    </div>
    
    <br>
    <button onclick="copySelected()">Copy Selected</button>&nbsp;&nbsp;
    <button onclick="moveSelected()">Move Selected</button>&nbsp;&nbsp;
    <button onclick="zipSelected()">Zip Selected</button>&nbsp;&nbsp;
    <button onclick="unzipSelected()">Unzip Selected</button>&nbsp;&nbsp;
    <button onclick="deleteSelected()">Delete Selected</button>&nbsp;&nbsp;
  
  @else
    <h2>Folder doesn't exist ❌</h2>
  @endif
  </div>
</main>
<br><br>

<script>

  const chunkSize = {{ env('FILE_UPLOAD_CHUNK_SIZE', 1024*1024) }};
  let uploadId = null;

  document.querySelector('form#fileUploader').addEventListener('submit', async (e) => {
    e.preventDefault();
    let uploadBtn = document.querySelector('#uploadBtn');
    uploadBtn.setAttribute('disabled', true);
    uploadBtn.innerHTML = 'Uploading File(s)... Please wait!';
    await uploadFiles();
  });

  async function uploadFiles() {
      
    const fileInput = document.getElementById('fileInput');
    const files = fileInput.files;

    for (const file of files) {
      const totalChunks = Math.ceil(file.size / chunkSize);
      uploadId = Date.now(); // Generate a unique upload ID

      for (let i = 0; i < totalChunks; i++) {
          const start = i * chunkSize;
          const end = Math.min(start + chunkSize, file.size);
          const chunk = file.slice(start, end);

          const formData = new FormData();
          formData.append('_file', chunk);
          formData.append('chunk_number', i + 1);
          formData.append('total_chunks', totalChunks);
          formData.append('upload_id', uploadId);

          formData.append('file', file.name);
          formData.append('dir', '{{ $currentDir }}');
          formData.append('_token', '{{ csrf_token() }}');
          formData.append('_token_', '{{ _token_generate() }}');

          await fetch('{{ route('uploadChunk') }}', {
              method: 'POST',
              body: formData,
          });
      }

      await fetch('{{ route('completeUpload') }}', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
          },
          body: JSON.stringify({
              upload_id: uploadId,
              total_chunks: totalChunks,
              file: file.name,
              dir: '{{ $currentDir }}',
              _token: '{{ csrf_token() }}',
              _token_: '{{ _token_generate() }}'
          }),
      });
    }

    alert('Upload completed');
    window.location.reload();
  }

  function revertFilesSelection() {
    let selectors = document.querySelectorAll('input[name=fileSelect]');
    selectors.forEach((slt, index) => {
      if (!slt.checked) {
        slt.checked = true;
      } else {
        slt.checked = false;
      }
    });
  }

  function createItem(type) {
    let newName = '';
    if (newName = prompt(type + ' name: ')) {
      window.location = "{!!
      route('createItem', [
        'dir' => $currentDir,
        '_token_' => _token_generate()
      ])
      !!}&type=" + type + '&file=' + newName;
    }
    
  }

  function renameFile(file) {
    let newName = '';
    if (newName = prompt('Rename: ', file)) {
      window.location = "{!! route('renameFile') !!}/?dir={!! $currentDir !!}&file=" + file + "&rename=" + newName + "&_token_={!! _token_generate() !!}";
    }
  }

  function getSelectedFiles() {
    let selectors = document.querySelectorAll('input[name=fileSelect]');
    let selected = [];
    selectors.forEach((slt, index) => {
      if (slt.checked) {
        selected.push(slt.value);
      }
    });
    return selected;
  }

  function copySelected() {
    let selected = getSelectedFiles();
    let destination = prompt('[Copy] Destination folder: ', '{{ $currentDir }}');
    if (selected.length > 0 && destination) {
      window.location = '{!! 
            route('copyFile', [
              'dir' => $currentDir, 
              '_token_' => _token_generate()
            ]) 
        !!}&file=' + selected.join('|') + '&to=' + destination; 
    }
  }

  function moveSelected() {
    let selected = getSelectedFiles();
    let destination = prompt('[Move] Destination folder: ', '{{ $currentDir }}');
    if (selected.length > 0 && destination) {
      window.location = '{!! 
            route('moveFile', [
              'dir' => $currentDir, 
              '_token_' => _token_generate()
            ]) 
        !!}&file=' + selected.join('|') + '&to=' + destination; 
    }
  }

  function deleteSelected() {
    let selected = getSelectedFiles();

    if (selected.length > 0) {
      if (confirm('Are you sure?')) { 
        window.location = '{!! 
            route('deleteFile', [
              'dir' => $currentDir, 
              '_token_' => _token_generate()
            ]) 
        !!}&file=' + selected.join('|'); 
      } 
    }
  }

  function zipSelected() {
    let selected = getSelectedFiles();
    if (selected.length > 0) {
      window.location = '{!! 
            route('zipFile', [
              'dir' => $currentDir, 
              '_token_' => _token_generate()
            ]) 
        !!}&file=' + selected.join('|'); 
    }
  }

  function unzipSelected() {
    let selected = getSelectedFiles();
    let destination = prompt('[Unzip] Destination folder: ', '{{ $currentDir }}');
    if (selected.length > 0 && destination) {
      window.location = '{!! 
            route('unzipFile', [
              'dir' => $currentDir, 
              '_token_' => _token_generate()
            ]) 
        !!}&file=' + selected.join('|') + '&to=' + destination; 
    }
  }

  window.addEventListener('DOMContentLoaded', function (e) {
    if (typeof confirm !== 'function' || typeof prompt !== 'function' ) {
      document.body.innerHTML = '<red><h2>Unsupported Browser! Please update your browser.</h2></red>';
    }
  });
</script>

<div class="banner" style="color: #888;">This File Manager is Developed by <a href="https://sadiq.us.to" target="_blank">Sadiq</a></div>

</body>
</html>
