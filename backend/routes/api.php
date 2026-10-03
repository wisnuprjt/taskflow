<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChunkedUploadController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:api')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    Route::get('users', [UserController::class, 'index']);

    Route::apiResource('tasks', TaskController::class);

    Route::post('tasks/{task}/attachments', [TaskAttachmentController::class, 'store']);
    Route::post('tasks/{task}/attachments/chunked', [ChunkedUploadController::class, 'init']);
    Route::post('uploads/{uploadId}/chunks', [ChunkedUploadController::class, 'chunk'])->whereUuid('uploadId');
    Route::post('uploads/{uploadId}/complete', [ChunkedUploadController::class, 'complete'])->whereUuid('uploadId');
    Route::delete('uploads/{uploadId}', [ChunkedUploadController::class, 'abort'])->whereUuid('uploadId');
    Route::get('attachments/{attachment}/download', [TaskAttachmentController::class, 'download'])->name('attachments.download');
    Route::get('attachments/{attachment}/thumbnail', [TaskAttachmentController::class, 'thumbnail'])->name('attachments.thumbnail');
    Route::get('attachments/{attachment}/versions', [TaskAttachmentController::class, 'versions']);
    Route::delete('attachments/{attachment}', [TaskAttachmentController::class, 'destroy']);

    Route::get('tasks/{task}/comments', [TaskCommentController::class, 'index']);
    Route::post('tasks/{task}/comments', [TaskCommentController::class, 'store']);
});
