@extends('layouts.app')

@section('title', 'Chat')

@section('sidebar')
    <div class="space-y-2">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">📚 Your Papers</p>
        @forelse($documents as $doc)
            <div class="flex items-center gap-2 text-xs text-slate-400 py-1.5 px-2 rounded-lg hover:bg-white/5 transition">
                <svg class="w-3.5 h-3.5 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="truncate">{{ $doc }}</span>
            </div>
        @empty
            <p class="text-xs text-slate-600 italic">No papers uploaded yet</p>
        @endforelse
    </div>
@endsection

@section('content')
<div class="flex flex-col h-screen">

    {{-- ── Header Bar ── --}}
    <header class="glass-light px-8 py-4 border-b border-indigo-500/10 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-100">💬 Research Chat</h2>
            <p class="text-xs text-slate-500">Ask questions about your uploaded research papers</p>
        </div>
        <div id="status-indicator" class="flex items-center gap-2 text-xs text-slate-500">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>{{ count($documents) }} paper{{ count($documents) !== 1 ? 's' : '' }} indexed</span>
        </div>
    </header>

    {{-- ── Messages Area ── --}}
    <div id="chat-messages" class="flex-1 overflow-y-auto px-8 py-6 space-y-6">

        {{-- Empty state --}}
        <div id="empty-state" class="flex flex-col items-center justify-center h-full text-center fade-in-up">
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-500/20 to-purple-600/20 flex items-center justify-center mb-6 border border-indigo-500/20">
                <svg class="w-10 h-10 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-slate-200 mb-2">What would you like to know?</h3>
            <p class="text-sm text-slate-500 mb-8 max-w-md">Ask me anything about your uploaded research papers. I'll find relevant sections and provide sourced answers.</p>

            <div class="grid grid-cols-2 gap-3 max-w-lg w-full">
                <button onclick="askSuggestion(this)" class="suggestion-btn glass-light rounded-xl p-4 text-left hover:border-indigo-500/30 transition group cursor-pointer">
                    <p class="text-xs font-medium text-indigo-400 mb-1">📋 Summary</p>
                    <p class="text-xs text-slate-400 group-hover:text-slate-300 transition">Summarize the key findings of the paper</p>
                </button>
                <button onclick="askSuggestion(this)" class="suggestion-btn glass-light rounded-xl p-4 text-left hover:border-indigo-500/30 transition group cursor-pointer">
                    <p class="text-xs font-medium text-indigo-400 mb-1">🔬 Methodology</p>
                    <p class="text-xs text-slate-400 group-hover:text-slate-300 transition">What methodology was used in this research?</p>
                </button>
                <button onclick="askSuggestion(this)" class="suggestion-btn glass-light rounded-xl p-4 text-left hover:border-indigo-500/30 transition group cursor-pointer">
                    <p class="text-xs font-medium text-indigo-400 mb-1">📊 Results</p>
                    <p class="text-xs text-slate-400 group-hover:text-slate-300 transition">What are the main results and conclusions?</p>
                </button>
                <button onclick="askSuggestion(this)" class="suggestion-btn glass-light rounded-xl p-4 text-left hover:border-indigo-500/30 transition group cursor-pointer">
                    <p class="text-xs font-medium text-indigo-400 mb-1">🔗 References</p>
                    <p class="text-xs text-slate-400 group-hover:text-slate-300 transition">What are the key references and related work?</p>
                </button>
            </div>
        </div>
    </div>

    {{-- ── Input Area ── --}}
    <div class="px-8 py-5 border-t border-indigo-500/10 glass-light">
        <form id="chat-form" class="flex items-end gap-3 max-w-4xl mx-auto">
            <div class="flex-1 relative">
                <textarea
                    id="question-input"
                    name="question"
                    rows="1"
                    placeholder="Ask about your research papers..."
                    class="w-full bg-dark-700/60 border border-indigo-500/15 rounded-2xl px-5 py-3.5 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500/40 focus:ring-2 focus:ring-indigo-500/10 resize-none transition"
                    style="min-height: 48px; max-height: 140px;"
                ></textarea>
            </div>
            <button
                type="submit"
                id="send-btn"
                class="bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-400 hover:to-purple-500 text-white rounded-2xl px-6 py-3.5 text-sm font-medium transition shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/40 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                <span>Send</span>
            </button>
        </form>
        <p class="text-center text-xs text-slate-600 mt-3">Answers are based on your uploaded research papers using RAG</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const chatMessages = document.getElementById('chat-messages');
    const chatForm = document.getElementById('chat-form');
    const questionInput = document.getElementById('question-input');
    const sendBtn = document.getElementById('send-btn');
    const emptyState = document.getElementById('empty-state');

    // Auto-resize textarea
    questionInput.addEventListener('input', function() {
        this.style.height = '48px';
        this.style.height = Math.min(this.scrollHeight, 140) + 'px';
    });

    // Enter to send (Shift+Enter for newline)
    questionInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.dispatchEvent(new Event('submit'));
        }
    });

    // Suggestion click
    function askSuggestion(btn) {
        const text = btn.querySelector('p:last-child').textContent;
        questionInput.value = text;
        chatForm.dispatchEvent(new Event('submit'));
    }

    // Submit handler
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const question = questionInput.value.trim();
        if (!question) return;

        // Hide empty state
        if (emptyState) emptyState.style.display = 'none';

        // Add user message
        addMessage('user', question);
        questionInput.value = '';
        questionInput.style.height = '48px';

        // Show typing indicator
        const typingId = showTyping();

        // Disable input
        sendBtn.disabled = true;
        questionInput.disabled = true;

        try {
            const response = await fetch('/chat/ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ question }),
            });

            removeTyping(typingId);

            const data = await response.json();

            if (data.error) {
                addMessage('error', data.error);
            } else {
                addMessage('ai', data.answer, data.sources);
            }
        } catch (err) {
            removeTyping(typingId);
            addMessage('error', 'Network error. Please check your connection.');
        } finally {
            sendBtn.disabled = false;
            questionInput.disabled = false;
            questionInput.focus();
        }
    });

    function addMessage(type, content, sources = []) {
        const wrapper = document.createElement('div');
        wrapper.className = 'fade-in-up';

        if (type === 'user') {
            wrapper.innerHTML = `
                <div class="flex justify-end">
                    <div class="max-w-2xl">
                        <div class="bg-gradient-to-r from-indigo-500/20 to-purple-600/20 border border-indigo-500/20 rounded-2xl rounded-br-md px-5 py-3">
                            <p class="text-sm text-slate-200 whitespace-pre-wrap">${escapeHtml(content)}</p>
                        </div>
                        <p class="text-xs text-slate-600 text-right mt-1.5">You · just now</p>
                    </div>
                </div>
            `;
        } else if (type === 'ai') {
            let sourcesHtml = '';
            if (sources && sources.length > 0) {
                sourcesHtml = `
                    <div class="mt-4 pt-3 border-t border-indigo-500/10">
                        <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-180')" class="flex items-center gap-2 text-xs text-indigo-400 hover:text-indigo-300 transition cursor-pointer mb-2">
                            <svg class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            ${sources.length} source${sources.length > 1 ? 's' : ''} referenced
                        </button>
                        <div class="hidden space-y-2">
                            ${sources.map((s, i) => `
                                <div class="glass-light rounded-lg p-3">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-medium text-indigo-300">📄 ${escapeHtml(s.source)}</span>
                                        <span class="text-xs text-slate-500">${(s.similarity * 100).toFixed(1)}% match</span>
                                    </div>
                                    <p class="text-xs text-slate-500 line-clamp-2">${escapeHtml(s.content)}</p>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            // Convert markdown-style formatting
            let formatted = escapeHtml(content)
                .replace(/\*\*(.*?)\*\*/g, '<strong class="text-slate-100">$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/^- (.+)$/gm, '<li class="ml-4 list-disc text-slate-300">$1</li>')
                .replace(/^(\d+)\. (.+)$/gm, '<li class="ml-4 list-decimal text-slate-300">$2</li>')
                .replace(/\n/g, '<br>');

            wrapper.innerHTML = `
                <div class="flex justify-start">
                    <div class="max-w-3xl">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                            </div>
                            <span class="text-xs font-medium text-indigo-400">Research AI</span>
                        </div>
                        <div class="glass rounded-2xl rounded-tl-md px-5 py-4 glow">
                            <div class="text-sm text-slate-300 leading-relaxed prose-sm">${formatted}</div>
                            ${sourcesHtml}
                        </div>
                        <p class="text-xs text-slate-600 mt-1.5">AI · just now</p>
                    </div>
                </div>
            `;
        } else if (type === 'error') {
            wrapper.innerHTML = `
                <div class="flex justify-center">
                    <div class="bg-red-500/10 border border-red-500/20 rounded-xl px-5 py-3 max-w-lg">
                        <p class="text-sm text-red-400 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                            ${escapeHtml(content)}
                        </p>
                    </div>
                </div>
            `;
        }

        chatMessages.appendChild(wrapper);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function showTyping() {
        const id = 'typing-' + Date.now();
        const div = document.createElement('div');
        div.id = id;
        div.className = 'fade-in-up';
        div.innerHTML = `
            <div class="flex justify-start">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </div>
                    <div class="glass rounded-2xl rounded-tl-md px-5 py-3 flex items-center gap-1.5">
                        <span class="text-xs text-slate-400 mr-2">Analyzing papers</span>
                        <span class="typing-dot w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                        <span class="typing-dot w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                        <span class="typing-dot w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                    </div>
                </div>
            </div>
        `;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        return id;
    }

    function removeTyping(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Focus input on load
    questionInput.focus();
</script>
@endpush
