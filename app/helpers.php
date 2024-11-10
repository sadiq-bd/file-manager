<?php

/**
 * Get Client Real IP Address
 * @return string
 */
function getClientIp() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = '0.0.0.0';
    }
    return $ipaddress;
}


function _get_request_info($dir = null, $file = null) {

    $rootDir = rtrim(env('FILE_ROOT_DIR', ''), '/');
    
    $currentDir = $dir === null ? request()->input('dir', '') : $dir;

    $currentDir = _clean_path($currentDir);

    $absDir = $rootDir . '/' . $currentDir;
    $absDir = rtrim($absDir, '/');

    $requestFile = $file ? $file : request()->input('file', '');

    $absFile = $absDir . '/' . $requestFile;

    return compact(
        'rootDir',
        'currentDir',
        'absDir',
        'requestFile',
        'absFile'
    );
}

function _clean_path($path) {

    $path = $path === null ? '' : $path;

    $path = preg_replace('#((\/+)?(\.\.)+(\/+)?)+#i', '/', $path);
    $path = preg_replace('#(\/\.\/)+#i', '/', $path);
    $path = trim($path, '.');
    $path = trim($path, '/');
    $path = trim($path, '.');

    return $path;
}


function _get_file_list(string $dir, bool $asObj = false, bool $ignoreDotDirs = false) {
    
	$fileList = [];
	if (is_dir($dir) && file_exists($dir)) {
		foreach(scandir($dir) as $index => $file) {

            if ($ignoreDotDirs && ($file == '.' || $file == '..')) {
                continue;
            }

			$baseFile = $file;
			$absPath = $dir . '/' . $baseFile;

			@$fileList[$index] = [
				'fileName' => $baseFile,
				'fileSize' => filesize($absPath),
				'fileType' => filetype($absPath),
				'fileMimeType' => mime_content_type($absPath),
				'fileOwner' => fileowner($absPath),
				'fileGroup' => filegroup($absPath),
				'filePermissions' => fileperms($absPath),
				'fileModificationTime' => filemtime($absPath),
				'isDir' => is_dir($absPath) && !is_file($absPath),
			];

            if ($asObj) {
                $fileList[$index] = (object) $fileList[$index];
            }
		}
	} else {
        return false;
    }

    if ($asObj) {
        return (object) $fileList;		// Object of Filelist
    }

    return $fileList;
}

function _format_size(int $size){

    if ($size <= 0) return 0;
    
    $base = log($size, 1024);
    
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    
    return round(pow(1024, $base-floor($base)), 2) . ' ' . $suffixes[floor($base)];
    
}

function _token_validate($token) {
    return session()->get('fileAccessToken', '') == $token 
        && time() < session()->get('fileAccessTokenExpiresAt', 0);
}

function _token_generate() {   

    $token_expiration = 3600;   // default token expiration 1h
    
    if (
        session()->get('fileAccessToken')
        && time() < session()->get('fileAccessTokenExpiresAt', 0)
    ) {
        $token = session()->get('fileAccessToken');
    } else {
        $token = base64_encode(md5(uniqid()));
    
        session()->put('fileAccessToken', $token);
        session()->put('fileAccessTokenExpiresAt', time() + $token_expiration);
    }

    return $token;
}


function removeDir($dir) {
   
    $dir = rtrim($dir, '/');

    if (isUseShellCommandsForFileHandling()) {

        exec('rm -rf ' . escapeshellarg($dir));

    } else {
        
        $dirFiles = array_diff(scandir($dir), array('.', '..'));

        foreach ($dirFiles as $df) {
            if (filetype($dir . '/' . $df) == 'dir') {
                removeDir($dir . '/' . $df);
            } else {
                unlink($dir . '/' . $df);
            }
        }

        rmdir($dir);
    }
}


function copyDir($dir, $to) {
    $dir = rtrim($dir, '/');

    if (isUseShellCommandsForFileHandling()) {
        
        exec('cp -r ' . escapeshellarg($dir) . ' ' . escapeshellarg($to));

    } else {

        $dirFiles = array_diff(scandir($dir), array('.', '..'));

        foreach ($dirFiles as $df) {
            if (filetype($dir . '/' . $df) == 'dir') {
                copyDir($dir . '/' . $df, $to . '/' . $df);
            } else {
                if (!file_exists($to)) mkdir($to, 0777, true);
                copy($dir . '/' . $df, $to . '/' . $df);
            }
        }
        
    }
}


function moveDir($dir, $to, $shouldRemoveDirs = true) {
    
    $dir = rtrim($dir, '/');

    if (isUseShellCommandsForFileHandling()) {
        
        exec('mv ' . escapeshellarg($dir) . ' ' . escapeshellarg($to));

    } else {

        $dirFiles = array_diff(scandir($dir), array('.', '..'));

        foreach ($dirFiles as $df) {
            if (filetype($dir . '/' . $df) == 'dir') {
                moveDir($dir . '/' . $df, $to . '/' . $df, $shouldRemoveDirs);
            } else {
                if (!file_exists($to)) mkdir($to, 0777, true);
                if (copy($dir . '/' . $df, $to . '/' . $df)) {
                    unlink($dir . '/' . $df);
                } else {
                    $shouldRemoveDirs = false;
                }
            }
        }

        if ($shouldRemoveDirs) {
            removeDir($dir);
        }

    }
}


function isUseShellCommandsForFileHandling() {
    return (bool) env('USE_SHELL_COMMANDS_FOR_FILE_HANDLING', true);
}
