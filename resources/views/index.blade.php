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
  @if($fileList !== false) 
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
    &nbsp; <i onclick="navigator.clipboard.writeText('{{ $currentDir }}')" style="cursor:pointer;">📋</i>
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
          $totalFiles = 0;
          $totalDirs = 0;
          $totalSizeOfFiles = 0;
        @endphp

        @if ($currentDir != '' && $currentDir != '.')
        <tr>
          <td></td>
          <td><a href="" style="font-size: 24px; padding: 8px;">.</a></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
        @endif
        @if ($currentDir != '' && $currentDir != '.' && $currentDir != '..')
        <tr>
          <td></td>
          <td><a style="font-size: 24px; padding: 8px;" href="{!! route('index', ['dir' => pathinfo($currentDir, PATHINFO_DIRNAME)]) !!}">..</a></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
        @endif

        @foreach($fileList as $index => $file)
        <tr data-file="{!! rawurlencode($file->fileName) !!}" data-is-dir="{{ $file->isDir ? 'true' : 'false' }}">
          <td><input type="checkbox" name="fileSelect"></td>
          <td><a id="fileLink"></a></td>
          <td>{{ $file->isDir ? '' : _format_size($file->fileSize) }}</td>
          <td>{{ $file->fileMimeType }}</td>
          <td>{{ date('d M y H:i:s', $file->fileModificationTime) }}</td>
          <td>
          <button onclick="renameFile(this)">rename</button>
          @if (!$file->isDir)
            @if (preg_match('#text|empty#i', $file->fileMimeType))
              <button onclick="editFile(this)">edit</button>
            @endif
          <button onclick="downloadFile(this)">download</button>
          @endif
          <button onclick="deleteFile(this)">delete</button>
          </td>
          @php
            if (!$file->isDir) { 
              $totalSizeOfFiles += $file->fileSize;
              $totalFiles++;
            } else {
              $totalDirs++;
            }
          @endphp
        </tr>
        @endforeach
        <tr>
          <td></td>
          <td><b style="color: #888;">Total <b style="color: #fff;">{{ $totalFiles }}</b> files and <b style="color: #fff;">{{ $totalDirs }}</b> folders in {{ $currentDir == '.' || $currentDir == '' ? '/' : $currentDir }}</b></td>
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

<script type="text/javascript">
  const chunkSize = {{ env('FILE_UPLOAD_CHUNK_SIZE', 1024*1024) }};
  const filesDelimiter = '/';

  const fileUploadForm = document.querySelector('form#fileUploader');
  const uploadBtn = document.querySelector('#uploadBtn');
  fileUploadForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    uploadBtn.setAttribute('disabled', "true");
    fileUploadForm.setAttribute('disabled', "true");
    uploadBtn.innerHTML = 'Uploading File(s)... Please wait!';
    await uploadFiles();
  });

  async function uploadFiles() {
    const fileInput = document.getElementById('fileInput');
    const files = fileInput.files;

    let uploadId = null;
    let currentFile = 0;

    while (currentFile < files.length) {
      let file = files[currentFile];
      const totalChunks = Math.ceil(file.size / chunkSize);
      uploadId = Date.now(); // Generate a unique upload ID

      let currentChunk = 0;
      while (currentChunk < totalChunks) {
        const start = currentChunk * chunkSize;
        const end = Math.min(start + chunkSize, file.size);
        const chunk = file.slice(start, end);

        const formData = new FormData();
        formData.append('_file', chunk);
        formData.append('chunk_number', currentChunk + 1);
        formData.append('total_chunks', totalChunks);
        formData.append('upload_id', uploadId);

        formData.append('file', file.name);
        formData.append('dir', '{{ $currentDir }}');
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('_token_', '{{ _token_generate() }}');

        let resp = await fetch('{{ route('uploadChunk') }}', {
            method: 'POST',
            body: formData
        });
        resp = await resp.json();
        if (resp.status == 'success') {
          currentChunk += 1;
        }

        let fname = file.name.length > 50 ? file.name.substring(0, 50) + '...' : file.name;
        let percentage = Math.round((((currentChunk + 1) * chunkSize ) / file.size) * 100);
        percentage = percentage > 100 ? 100 : percentage;
        uploadBtn.innerHTML = (currentFile + 1) + '. Uploading '+ fname +' ( '+ percentage +' % )';
      }

      let resp = await fetch('{{ route('completeUpload') }}', {
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
        })
      });
      resp = await resp.json();
      if (resp.status == 'success') {
        currentFile += 1;
      }
    }
    
    uploadBtn.innerHTML = 'Upload completed... reloading!';
    window.location.reload();
  }
 
  function getSelectedFiles() {
    let selectors = document.querySelectorAll('input[name=fileSelect]');
    let selected = [];
    selectors.forEach((slt, index) => {
      if (slt.checked) {
        selected.push(decodeURIComponent(slt.value));
      }
    });
    return selected;
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
    let newName = prompt(type.charAt(0).toUpperCase() + type.slice(1) + ' name: ');
    if (newName) {
      window.location = "{!! route('createItem', [ 'dir' => $currentDir, '_token_' => _token_generate() ]) !!}&type=" + type + "&file=" + encodeURIComponent(newName);
    } 
  }

  function renameFile(file) {
    if (typeof file != 'string') {
      file = file.parentElement.parentElement.dataset.file;
    }
    file = decodeURIComponent(file);
    let newName = prompt('Rename: ', file);
    if (newName) {
      window.location = "{!! route('renameFile', [ 'dir' => $currentDir, '_token_' => _token_generate() ]) !!}&file=" + encodeURIComponent(file) + "&rename=" + encodeURIComponent(newName);
    }
  }

  function editFile(file) {
    if (typeof file != 'string') {
      file = file.parentElement.parentElement.dataset.file;
    }
    file = decodeURIComponent(file);
    window.location = "{!! route('editFile', [ 'dir' => $currentDir, '_token_' => _token_generate() ]) !!}&file=" + encodeURIComponent(file);
  }

  function downloadFile(file) {
    if (typeof file != 'string') {
      file = file.parentElement.parentElement.dataset.file;
    }
    file = decodeURIComponent(file);
    window.location = "{!! route('downloadFile', [ 'dir' => $currentDir, '_token_' => _token_generate() ]) !!}&file=" + encodeURIComponent(file);
  }

  function deleteFile(file) {
    if (typeof file != 'string') {
      file = file.parentElement.parentElement.dataset.file;
    }
    file = decodeURIComponent(file);
    if (confirm('Delete confirmation for file: ' + file)) {
        window.location = "{!! route('deleteFile', [ 'dir' => $currentDir, '_token_' => _token_generate() ]) !!}&file=" + encodeURIComponent(file);
    }  
}

  function copySelected() {
    let selected = getSelectedFiles();
    let destination = prompt('[Copy] Destination folder: ', '{{ $currentDir }}');
    if (selected.length > 0 && destination) {
      window.location = "{!! route('copyFile', [ 'dir' => $currentDir,  '_token_' => _token_generate() ]) !!}&file=" + encodeURIComponent(selected.join(filesDelimiter)) + "&to=" + encodeURIComponent(destination); 
    }
  }

  function moveSelected() {
    let selected = getSelectedFiles();
    let destination = prompt('[Move] Destination folder: ', '{{ $currentDir }}');
    if (selected.length > 0 && destination) {
      window.location = "{!! route('moveFile', [ 'dir' => $currentDir, '_token_' => _token_generate() ]) !!}&file=" + encodeURIComponent(selected.join(filesDelimiter)) + "&to=" + encodeURIComponent(destination); 
    }
  }

  function deleteSelected() {
    let selected = getSelectedFiles();
    if (selected.length > 0) {
      if (confirm('Are you sure want to delete '+selected.length+' files?')) { 
        window.location = "{!! route('deleteFile', [ 'dir' => $currentDir, '_token_' => _token_generate() ]) !!}&file=" + encodeURIComponent(selected.join(filesDelimiter)); 
      } 
    }
  }

  function zipSelected() {
    let selected = getSelectedFiles();
    if (selected.length > 0) {
      window.location = "{!! route('zipFile', [ 'dir' => $currentDir, '_token_' => _token_generate() ]) !!}&file=" + encodeURIComponent(selected.join(filesDelimiter)); 
    }
  }

  function unzipSelected() {
    let selected = getSelectedFiles();
    let destination = prompt('[Unzip] Destination folder: ', '{{ $currentDir }}');
    if (selected.length > 0 && destination) {
      window.location = "{!! route('unzipFile', [ 'dir' => $currentDir, '_token_' => _token_generate() ]) !!}&file=" + encodeURIComponent(selected.join(filesDelimiter)) + "&to=" + encodeURIComponent(destination); 
    }
  }

  window.addEventListener('DOMContentLoaded', function (e) {
    if (typeof confirm !== 'function' || typeof prompt !== 'function' ) {
      document.body.innerHTML = '<red><h2>Unsupported Browser! Please update your browser.</h2></red>';
    }

    // set file checkbox values
    document.querySelectorAll('input[name=fileSelect]').forEach(checkbox => {
      checkbox.value = checkbox.parentElement.parentElement.dataset.file;
    });
    
    // set file links
    document.querySelectorAll('a#fileLink').forEach(link => {
      let fname = decodeURIComponent(link.parentElement.parentElement.dataset.file);
      let isDir = link.parentElement.parentElement.dataset.isDir;
      if (isDir == 'true') {
        link.href = "{!! route('index', ['dir' => $currentDir]) !!}" + encodeURIComponent("/" + fname);
        link.innerHTML = '/&nbsp;' + fname.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;") + '&nbsp;/';
      } else {
        link.href = "{!! route('viewFile', ['dir' => $currentDir, '_token_' => _token_generate()]) !!}&file=" + encodeURIComponent(fname);
        link.innerText = fname;
        link.target = '_blank';
      }
      
    });
  });
</script>

<div class="banner" style="color: #888;">This File Manager is Developed by <a href="https://sadiq.us.to" target="_blank">Sadiq</a></div>

</body>
</html>
