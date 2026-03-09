<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ChatController;

// ── Main Routes ──
Route::get('/', function () {
    return redirect('/chat');
});

// ── Chat ──
Route::get('/chat', [ChatController::class, 'showChat']);
Route::post('/chat/ask', [ChatController::class, 'ask']);

// ── Document Upload ──
Route::get('/upload', [DocumentController::class, 'showUploadForm']);
Route::post('/upload', [DocumentController::class, 'upload']);

// ── API ──
Route::get('/api/documents', [ChatController::class, 'documents']);
