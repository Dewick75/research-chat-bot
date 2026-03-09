<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Research AI') — Research Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        dark: { 50: '#f8fafc', 100: '#e2e8f0', 200: '#94a3b8', 300: '#64748b', 400: '#475569', 500: '#334155', 600: '#1e293b', 700: '#0f172a', 800: '#0b1120', 900: '#060a14' },
                        accent: { 400: '#818cf8', 500: '#6366f1', 600: '#4f46e5' },
                        emerald: { 400: '#34d399', 500: '#10b981' },
                    }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: linear-gradient(135deg, #060a14 0%, #0b1120 50%, #0f172a 100%); }
        .glass { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(20px); border: 1px solid rgba(99, 102, 241, 0.1); }
        .glass-light { background: rgba(30, 41, 59, 0.4); backdrop-filter: blur(12px); border: 1px solid rgba(99, 102, 241, 0.08); }
        .glow { box-shadow: 0 0 40px rgba(99, 102, 241, 0.1); }
        .nav-link { transition: all 0.3s ease; }
        .nav-link:hover { background: rgba(99, 102, 241, 0.1); transform: translateX(4px); }
        .nav-link.active { background: rgba(99, 102, 241, 0.15); border-left: 3px solid #6366f1; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.3); border-radius: 3px; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in-up { animation: fadeInUp 0.4s ease-out; }
        @keyframes pulse-dot {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
            40% { transform: scale(1); opacity: 1; }
        }
        .typing-dot { animation: pulse-dot 1.4s infinite ease-in-out; }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen text-slate-200 antialiased">
    <div class="flex min-h-screen">

        {{-- ── Sidebar ── --}}
        <aside class="w-72 glass flex flex-col fixed h-full z-10">
            {{-- Logo --}}
            <div class="p-6 border-b border-indigo-500/10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">Research AI</h1>
                        <p class="text-xs text-slate-500">Intelligent Paper Analysis</p>
                    </div>
                </div>
            </div>

            {{-- Nav --}}
            <nav class="flex-1 p-4 space-y-1">
                <a href="/chat" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium {{ request()->is('chat*') ? 'active text-indigo-300' : 'text-slate-400 hover:text-slate-200' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    Chat with Papers
                </a>
                <a href="/upload" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium {{ request()->is('upload*') ? 'active text-indigo-300' : 'text-slate-400 hover:text-slate-200' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    Upload Papers
                </a>
            </nav>

            {{-- Documents --}}
            @hasSection('sidebar')
                <div class="p-4 border-t border-indigo-500/10">
                    @yield('sidebar')
                </div>
            @endif

            {{-- Footer --}}
            <div class="p-4 border-t border-indigo-500/10">
                <div class="glass-light rounded-xl p-3">
                    <p class="text-xs text-slate-500 text-center">Powered by Groq + HuggingFace</p>
                    <p class="text-xs text-slate-600 text-center mt-1">pgvector on Supabase</p>
                </div>
            </div>
        </aside>

        {{-- ── Main Content ── --}}
        <main class="flex-1 ml-72">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
