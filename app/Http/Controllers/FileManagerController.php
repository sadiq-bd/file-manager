<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Libraries\Zipper;

class FileManagerController extends Controller
{

	public const FILES_DELIMITER = '/';
	
    public function index(Request $request) {

		$reqInfo = _get_request_info();

		$rootDir = $reqInfo['rootDir'];
		$currentDir = $reqInfo['currentDir'];
		$absDir = $reqInfo['absDir'];
		$requestFile = $reqInfo['requestFile'];

		$fileList = _get_file_list($absDir, true, true);
		
		return view('index', compact(
			'fileList',
			'currentDir',
			'requestFile',
			'rootDir'
		));
		
    }

	public function viewFile(Request $request) {

		$reqInfo = _get_request_info();

		$rootDir = $reqInfo['rootDir'];
		$currentDir = $reqInfo['currentDir'];
		$absDir = $reqInfo['absDir'];
		$requestFile = $reqInfo['requestFile'];
		$absFile = $reqInfo['absFile'];

		if (file_exists($absFile) && is_file($absFile)) {
			return response()->file($absFile);
		} else {
			return response(null, 404);
		}

	}


	public function downloadFile(Request $request) {

        $reqInfo = _get_request_info();

        $rootDir = $reqInfo['rootDir'];
        $currentDir = $reqInfo['currentDir'];
        $absDir = $reqInfo['absDir'];
        $requestFile = $reqInfo['requestFile'];
        $absFile = $reqInfo['absFile'];

        if (file_exists($absFile) && is_file($absFile)) {
            if (config('filemanager.nginx_x_sendfile_path')) {
				return response('', 200, [
					'Content-Type' => mime_content_type($absFile),
                	'Content-Disposition' => 'attachment; filename="' . $requestFile . '"',
					'X-Accel-Redirect' => '/' 
						. trim(config('filemanager.nginx_x_sendfile_path'), '/') 
						. '/' 
						. _clean_path($currentDir) 
						. '/'
						. $requestFile
				]);
			} else {
				return response()->download($absFile);
			}
        } else {
			return response(null, 404);
        }

    }


	public function uploadChunk(Request $request) {

		$reqInfo = _get_request_info();

        $rootDir = $reqInfo['rootDir'];
        $currentDir = $reqInfo['currentDir'];
        $absDir = $reqInfo['absDir'];
        $requestFile = $reqInfo['requestFile'];
        $absFile = $reqInfo['absFile'];

        $file = $request->file('_file');
        $chunkNumber = $request->input('chunk_number');
        $totalChunks = $request->input('total_chunks');
        $fileName = $request->input('file');
        $uploadId = $request->input('upload_id');

        $chunkPath = config('filemanager.temp_dir') . '/' . $uploadId . '/' . $chunkNumber;
        $file->move(dirname($chunkPath), basename($chunkPath));

        return response()->json(['status' => 'success']);
    }

    public function completeUpload(Request $request) {

		$reqInfo = _get_request_info();

        $rootDir = $reqInfo['rootDir'];
        $currentDir = $reqInfo['currentDir'];
        $absDir = $reqInfo['absDir'];
        $requestFile = $reqInfo['requestFile'];
        $absFile = $reqInfo['absFile'];

        $uploadId = $request->input('upload_id');
        $totalChunks = (int) $request->input('total_chunks');

		$fnameReal = $requestFile;
		$fnameBase = pathinfo($fnameReal, PATHINFO_FILENAME);
		$fnameExt = pathinfo($fnameReal, PATHINFO_EXTENSION);
		if (!file_exists($absDir . '/' . $fnameReal)) {
			$fname = $absDir . '/' . $fnameReal;
		} else {
			
			$dublicateNo = 1;
			while (file_exists($absDir . '/' . $fnameBase . '('.$dublicateNo.').' . $fnameExt)) {
				$dublicateNo++;
			}
			$fname = $absDir . '/' . $fnameBase . '('.$dublicateNo.').' . $fnameExt;
		}

        $fileHandle = fopen($fname, 'a');

        for ($i = 1; $i <= $totalChunks; $i++) {
            $chunkPath = config('filemanager.temp_dir') . '/' . $uploadId . '/' . $i;
            $chunkHandle = fopen($chunkPath, 'r');
            stream_copy_to_stream($chunkHandle, $fileHandle);
            fclose($chunkHandle);
            unlink($chunkPath); // Delete the chunk
        }

        fclose($fileHandle);
        rmdir(config('filemanager.temp_dir') . '/' . $uploadId); // Delete the chunk directory

        return response()->json(['status' => 'success']);
    }


	/**
	 * For direct upload
	 */
	public function uploadFile(Request $request) {
		
		$reqInfo = _get_request_info();

		$rootDir = $reqInfo['rootDir'];
		$currentDir = $reqInfo['currentDir'];
		$absDir = $reqInfo['absDir'];
		$requestFile = $reqInfo['requestFile'];
		$absFile = $reqInfo['absFile'];

		$files = $request->file()['file'];

		foreach ($files as $fl) {
		
			$fnameReal = $fl->getClientOriginalName();
			$fnameBase = pathinfo($fnameReal, PATHINFO_FILENAME);
			$fnameExt = pathinfo($fnameReal, PATHINFO_EXTENSION);

			if (!file_exists($absDir . '/' . $fnameReal)) {
				$fname = $absDir . '/' . $fnameReal;
			} else {
				
				$dublicateNo = 1;
				while (file_exists($absDir . '/' . $fnameBase . '('.$dublicateNo.').' . $fnameExt)) {
					$dublicateNo++;
				}
				$fname = $absDir . '/' . $fnameBase . '('.$dublicateNo.').' . $fnameExt;
			}
			
			$fl->move($absDir, $fname);

		}

		return redirect()->back();
	}


	public function renameFile(Request $request) {

		$newName = $request->input('rename');

		$reqInfo = _get_request_info();

		$rootDir = $reqInfo['rootDir'];
		$currentDir = $reqInfo['currentDir'];
		$absDir = $reqInfo['absDir'];
		$requestFile = $reqInfo['requestFile'];
		$absFile = $reqInfo['absFile'];

		if (file_exists($absFile)) {
			rename($absFile, $absDir . '/' . $newName);
		}

		return redirect()->back();
		
	}


	public function editFile(Request $request) {

		$reqInfo = _get_request_info();

		$rootDir = $reqInfo['rootDir'];
		$currentDir = $reqInfo['currentDir'];
		$absDir = $reqInfo['absDir'];
		$requestFile = $reqInfo['requestFile'];
		$absFile = $reqInfo['absFile'];

		if (is_file($absFile) && file_exists($absFile)) {

			$file = [
					'fileName' => $requestFile,
					'fileSize' => filesize($absFile),
					'fileType' => filetype($absFile),
					'fileMimeType' => mime_content_type($absFile),
					'fileOwner' => fileowner($absFile),
					'fileGroup' => filegroup($absFile),
					'filePermissions' => fileperms($absFile),
					'fileModificationTime' => filemtime($absFile),
					'isDir' => is_dir($absFile) && !is_file($absFile),
					'content' => file_get_contents($absFile),
			];
			
			$file = (object) $file;

			return view('edit', compact(
				'file',
				'currentDir'
			));

		} else {

			return response(null, 404);

		}
		
	}

	public function editFileAction(Request $request) {

		$reqInfo = _get_request_info();

		$rootDir = $reqInfo['rootDir'];
		$currentDir = $reqInfo['currentDir'];
		$absDir = $reqInfo['absDir'];
		$requestFile = $reqInfo['requestFile'];
		$absFile = $reqInfo['absFile'];

		$name = $request->input('name');
		$content = $request->input('content');

		file_put_contents($absFile, $content);

		if ($requestFile !== $name) {
			rename($absFile, $absDir . '/' . $name);
		}

		return redirect()->route('editFile', [
			'dir' => $currentDir,
			'file' => $name,
			'_token_' => _token_generate()
		]);
	}


	public function deleteFile(Request $request) {

		$newName = $request->input('rename');

		$reqInfo = _get_request_info();

		$rootDir = $reqInfo['rootDir'];
		$currentDir = $reqInfo['currentDir'];
		$absDir = $reqInfo['absDir'];
		$requestFile = $reqInfo['requestFile'];
		$absFile = $reqInfo['absFile'];

		if (rtrim($absFile, '/') != $rootDir) {
			if (strpos($requestFile, self::FILES_DELIMITER) !== false) {
				$reqFiles = @explode(self::FILES_DELIMITER, $requestFile);
			} else {
				$reqFiles = [$requestFile];
			}
			foreach ($reqFiles as $rf) {

				$reqInfo = _get_request_info(null, $rf);
				if (is_file($reqInfo['absFile']) && file_exists($reqInfo['absFile'])) {
					if (isUseShellCommandsForFileHandling()) {
						exec('rm -f ' . escapeshellarg($reqInfo['absFile']));
					} else {
						unlink($reqInfo['absFile']);
					}
				} else {
					removeDir($reqInfo['absFile']);	// Remove dir recursively
				}
			}
		}

		return redirect()->back();
		
	}


	public function copyFile(Request $request) {

		$reqInfo = _get_request_info();

		$rootDir = $reqInfo['rootDir'];
		$currentDir = $reqInfo['currentDir'];
		$absDir = $reqInfo['absDir'];
		$requestFile = $reqInfo['requestFile'];
		$absFile = $reqInfo['absFile'];

		$to = $request->input('to');
		$to = ltrim($to, '/');
		$absTo = $rootDir . '/' . $to;

		if (is_dir($absTo) && file_exists($absTo)) {

			if (rtrim($absFile, '/') != $rootDir) {
				if (strpos($requestFile, self::FILES_DELIMITER) !== false) {
					$reqFiles = @explode(self::FILES_DELIMITER, $requestFile);
				} else {
					$reqFiles = [$requestFile];
				}
				foreach ($reqFiles as $rf) {

					$reqInfo = _get_request_info(null, $rf);
					if (is_file($reqInfo['absFile']) && file_exists($reqInfo['absFile'])) {
						if (isUseShellCommandsForFileHandling()) {
							exec('cp ' . escapeshellarg($reqInfo['absFile']) . ' ' . escapeshellarg(rtrim($absTo, '/') . '/' . $reqInfo['requestFile']));
						} else {
							copy($reqInfo['absFile'], rtrim($absTo, '/') . '/' . $reqInfo['requestFile']);
						}
						
					} else {
						copyDir($reqInfo['absFile'], rtrim($absTo, '/') . '/' . $reqInfo['requestFile']);	// copy dir recursively
					}
					
				}
			}

		}

		return redirect()->back();
		
	}


	public function moveFile(Request $request) {

		$reqInfo = _get_request_info();

		$rootDir = $reqInfo['rootDir'];
		$currentDir = $reqInfo['currentDir'];
		$absDir = $reqInfo['absDir'];
		$requestFile = $reqInfo['requestFile'];
		$absFile = $reqInfo['absFile'];

		$to = $request->input('to');
		$to = ltrim($to, '/');
		$absTo = $rootDir . '/' . $to;

		if (is_dir($absTo) && file_exists($absTo)) {

			if (rtrim($absFile, '/') != $rootDir) {
				if (strpos($requestFile, self::FILES_DELIMITER) !== false) {
					$reqFiles = @explode(self::FILES_DELIMITER, $requestFile);
				} else {
					$reqFiles = [$requestFile];
				}
				foreach ($reqFiles as $rf) {

					$reqInfo = _get_request_info(null, $rf);
					if (is_file($reqInfo['absFile']) && file_exists($reqInfo['absFile'])) {
						if (isUseShellCommandsForFileHandling()) {
							exec('mv ' . escapeshellarg($reqInfo['absFile']) . ' ' . escapeshellarg(rtrim($absTo, '/') . '/' . $reqInfo['requestFile']));
						} else {
							if (copy($reqInfo['absFile'], rtrim($absTo, '/') . '/' . $reqInfo['requestFile'])) {
								unlink($reqInfo['absFile']);
							}
						}
						
					} else {
						moveDir($reqInfo['absFile'], rtrim($absTo, '/') . '/' . $reqInfo['requestFile']);		// move dir recursively
					}
					
				}
			}

		}

		return redirect()->back();

	}


	public function zipFile(Request $request) {

		$reqInfo = _get_request_info();

		$rootDir = $reqInfo['rootDir'];
		$currentDir = $reqInfo['currentDir'];
		$absDir = $reqInfo['absDir'];
		$requestFile = $reqInfo['requestFile'];
		$absFile = $reqInfo['absFile'];

		if (rtrim($absFile, '/') != $rootDir) {
			if (strpos($requestFile, self::FILES_DELIMITER) !== false) {
				$reqFiles = @explode(self::FILES_DELIMITER, $requestFile);
			} else {
				$reqFiles = [$requestFile];
			}

			$arcName = 'Archive_' . date('d_M_Y_H_i_s') . '.zip';
			chdir($absDir);
			
			if (isUseShellCommandsForFileHandling()) {
				$reqFilesShellEsc = [];
				foreach ($reqFiles as $index => $f) {
					$reqFilesShellEsc[$index] = escapeshellarg($f);
				}
				exec('zip "' . $arcName . '" ' . implode(' ', $reqFilesShellEsc));
			} else {
				if (class_exists('ZipArchive')) {
					
					$zipper = new Zipper;

					$zipper->create($arcName, $reqFiles);
				} else {
					return response('ZipArchive Extension not found!');
				}
			}
			
			
		}

		return redirect()->back();
		
	}



	public function unzipFile(Request $request) {

		$reqInfo = _get_request_info();

		$rootDir = $reqInfo['rootDir'];
		$currentDir = $reqInfo['currentDir'];
		$absDir = $reqInfo['absDir'];
		$requestFile = $reqInfo['requestFile'];
		$absFile = $reqInfo['absFile'];

		$to = $request->input('to');
		$to = ltrim($to, '/');
		$absTo = $rootDir . '/' . $to;

		if (rtrim($absFile, '/') != $rootDir) {
			if (strpos($requestFile, self::FILES_DELIMITER) !== false) {
				$reqFiles = @explode(self::FILES_DELIMITER, $requestFile);
			} else {
				$reqFiles = [$requestFile];
			}
			
			foreach ($reqFiles as $rf) {

				$reqInfo = _get_request_info(null, $rf);

				if (is_file($reqInfo['absFile']) && file_exists($reqInfo['absFile']) && pathinfo($reqInfo['absFile'], PATHINFO_EXTENSION) == 'zip') {
					if (isUseShellCommandsForFileHandling()) {
						exec('unzip "' . $reqInfo['absFile'] . '" -d ' . escapeshellarg($absTo));
					} else {
						if (class_exists('ZipArchive')) {
							if (!file_exists($absTo) && !is_file($absTo)) mkdir($absTo, 0777, true);
							$zipper = new Zipper;
							$zipper->unzip($reqInfo['absFile'], $absTo);
						} else {
							return response('ZipArchive Extension not found!');
						}
					}
				}
				
			}
			
		}

		return redirect()->back();
		
	}


	public function createItem(Request $request) {

		$reqInfo = _get_request_info();

		$rootDir = $reqInfo['rootDir'];
		$currentDir = $reqInfo['currentDir'];
		$absDir = $reqInfo['absDir'];
		$requestFile = $reqInfo['requestFile'];
		$absFile = $reqInfo['absFile'];

		$type = strtolower($request->input('type', 'dir'));

		if (!file_exists($absFile)) {
			switch ($type) {
				case 'file':
					if (!file_exists(pathinfo($absFile, PATHINFO_DIRNAME))) {
						mkdir(pathinfo($absFile, PATHINFO_DIRNAME), 0777, true);
					}
					touch($absFile);
					break;
				case 'dir':
				case 'folder':
					mkdir($absFile, 0777, true);
					break;
			}
		}
		

		return redirect()->back();

	}
	

}
