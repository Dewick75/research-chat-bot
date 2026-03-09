@extends('layouts.app')

@section('title', 'Upload Papers')

@section('content')
<div class="flex items-center justify-center min-h-screen px-8 py-12">
    <div class="w-full max-w-xl fade-in-up">

        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500/20 to-purple-600/20 flex items-center justify-center mx-auto mb-4 border border-indigo-500/20">
                <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            </div>
            <h2 class="text-2xl font-bold text-slate-100 mb-2">Upload Research Paper</h2>
            <p class="text-sm text-slate-500">Upload a PDF to chunk, embed, and index into your vector database</p>
        </div>

        {{-- Alerts --}}
        @if(session('status'))
            <div class="mb-6 bg-emerald-500/10 border border-emerald-500/20 rounded-xl px-5 py-3 fade-in-up">
                <p class="text-sm text-emerald-400 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('status') }}
                </p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-500/10 border border-red-500/20 rounded-xl px-5 py-3 fade-in-up">
                <p class="text-sm text-red-400 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    {{ session('error') }}
                </p>
            </div>
        @endif

        {{-- Upload Card --}}
        <div class="glass rounded-2xl p-8 glow">
            <form action="/upload" method="POST" enctype="multipart/form-data" id="upload-form">
                @csrf

                {{-- Drop Zone --}}
                <div id="drop-zone" class="border-2 border-dashed border-indigo-500/20 rounded-xl p-8 text-center hover:border-indigo-500/40 transition cursor-pointer mb-6" onclick="document.getElementById('file-input').click()">
                    <svg class="w-10 h-10 text-slate-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="text-sm text-slate-400 mb-1" id="file-label">Click to select or drag a PDF file</p>
                    <p class="text-xs text-slate-600">PDF files only, max 10MB</p>
                    <input type="file" name="file" id="file-input" accept=".pdf" class="hidden" onchange="handleFileSelect(this)">
                </div>

                {{-- Submit --}}
                <button type="submit" id="submit-btn" class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-400 hover:to-purple-500 text-white font-semibold py-3.5 px-6 rounded-xl transition shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/40 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <span id="btn-text">Process & Index Paper</span>
                </button>
            </form>
        </div>

        {{-- Info --}}
        <div class="mt-6 grid grid-cols-3 gap-3">
            <div class="glass-light rounded-xl p-4 text-center">
                <p class="text-lg font-bold text-indigo-400">1</p>
                <p class="text-xs text-slate-500 mt-1">Parse PDF text</p>
            </div>
            <div class="glass-light rounded-xl p-4 text-center">
                <p class="text-lg font-bold text-indigo-400">2</p>
                <p class="text-xs text-slate-500 mt-1">Chunk & embed</p>
            </div>
            <div class="glass-light rounded-xl p-4 text-center">
                <p class="text-lg font-bold text-indigo-400">3</p>
                <p class="text-xs text-slate-500 mt-1">Store vectors</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function handleFileSelect(input) {
        const label = document.getElementById('file-label');
        const zone = document.getElementById('drop-zone');
        if (input.files.length > 0) {
            label.textContent = '📄 ' + input.files[0].name;
            label.classList.remove('text-slate-400');
            label.classList.add('text-indigo-300', 'font-medium');
            zone.classList.add('border-indigo-500/40', 'bg-indigo-500/5');
        }
    }

    // Show loading state on form submit
    document.getElementById('upload-form').addEventListener('submit', function() {
        const btn = document.getElementById('submit-btn');
        const btnText = document.getElementById('btn-text');
        btn.disabled = true;
        btnText.textContent = 'Processing... This may take a minute ⏳';
    });

    // Drag & drop
    const dropZone = document.getElementById('drop-zone');
    dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('border-indigo-500/50', 'bg-indigo-500/5'); });
    dropZone.addEventListener('dragleave', () => { dropZone.classList.remove('border-indigo-500/50', 'bg-indigo-500/5'); });
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-indigo-500/50', 'bg-indigo-500/5');
        const fileInput = document.getElementById('file-input');
        fileInput.files = e.dataTransfer.files;
        handleFileSelect(fileInput);
    });
</script>
@endpush