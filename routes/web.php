<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileManagerController;


Route::group(['middleware' => App\Http\Middleware\BasicAuth::class], function () {

	Route::get('/', [FileManagerController::class, 'index'])->name('index');

	Route::group(['middleware' => App\Http\Middleware\TokenValidation::class], function () {
		Route::get('/viewFile', [FileManagerController::class, 'viewFile'])->name('viewFile');
		Route::post('/uploadFile', [FileManagerController::class, 'uploadFile'])->name('uploadFile');	// for direct upload
		Route::post('/uploadFile/chunk', [FileManagerController::class, 'uploadChunk'])->name('uploadChunk');
		Route::post('/uploadFile/done', [FileManagerController::class, 'completeUpload'])->name('completeUpload');
		Route::get('/downloadFile', [FileManagerController::class, 'downloadFile'])->name('downloadFile');
		Route::get('/createItem', [FileManagerController::class, 'createItem'])->name('createItem');
		Route::get('/renameFile', [FileManagerController::class, 'renameFile'])->name('renameFile');
		Route::get('/editFile', [FileManagerController::class, 'editFile'])->name('editFile');
		Route::post('/editFile', [FileManagerController::class, 'editFileAction'])->name('editFileAction');
		Route::get('/deleteFile', [FileManagerController::class, 'deleteFile'])->name('deleteFile');	
		Route::get('/copyFile', [FileManagerController::class, 'copyFile'])->name('copyFile');	
		Route::get('/moveFile', [FileManagerController::class, 'moveFile'])->name('moveFile');	
		Route::get('/zipFile', [FileManagerController::class, 'zipFile'])->name('zipFile');	
		Route::get('/unzipFile', [FileManagerController::class, 'unzipFile'])->name('unzipFile');	
	});

});

Route::fallback(function() {
	return response()->json([
		'status' => 'ERR',
		'message' => 'Access Denied'
	], 403, [], JSON_PRETTY_PRINT);
});
