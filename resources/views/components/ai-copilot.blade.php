{{-- IMS2 AI Copilot Floating Assistant Widget --}}
<style>
/* Styling markdown tables & formatting inside AI Chat */
.ai-content-body table {
    width: 100%;
    margin: 0.5rem 0;
    border-collapse: collapse;
    font-size: 11px;
}
.ai-content-body th, .ai-content-body td {
    border: 1px solid rgba(51, 65, 85, 0.8);
    padding: 4px 6px;
    text-align: left;
}
.ai-content-body th {
    background: rgba(15, 23, 42, 0.9);
    color: #38bdf8;
    font-weight: 600;
}
.ai-content-body code {
    background: rgba(15, 23, 42, 0.8);
    color: #22d3ee;
    padding: 1px 4px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 10.5px;
}
.ai-content-body ul {
    list-style-type: disc;
    padding-left: 1.25rem;
    margin: 0.25rem 0;
}
.ai-content-body ol {
    list-style-type: decimal;
    padding-left: 1.25rem;
    margin: 0.25rem 0;
}
.ai-content-body blockquote {
    border-left: 3px solid #06b6d4;
    padding-left: 0.5rem;
    color: #94a3b8;
    margin: 0.25rem 0;
    font-style: italic;
}
</style>

<script>
window.imsAiCopilot = function() {
    return {
        isOpen: false,
        isLoading: false,
        userInput: '',
        engineMode: 'AI Copilot',
        quickChips: [
            { icon: '📊', label: 'Rekap Hari Ini', prompt: 'Rekap operasional IMS2 hari ini' },
            { icon: '💳', label: 'Tagihan Pending', prompt: 'Cek tagihan pending verifikasi' },
            { icon: '🌐', label: 'Status OLT', prompt: 'Status OLT dan jaringan' },
            { icon: '🎫', label: 'Tiket Open', prompt: 'Cek tiket gangguan yang masih open' },
            { icon: '👥', label: 'Cari Pelanggan', prompt: 'Cek pelanggan aktif' }
        ],
        messages: [],

        init() {
            // Restore ongoing session messages from sessionStorage
            try {
                const saved = sessionStorage.getItem('ims_ai_chat_messages');
                if (saved) {
                    this.messages = JSON.parse(saved);
                } else {
                    this.addWelcomeMessage();
                }
            } catch (e) {
                this.addWelcomeMessage();
            }

            // Check API status
            this.checkStatus();
        },

        addWelcomeMessage() {
            this.messages = [{
                role: 'assistant',
                text: "Halo! 👋 Saya adalah **IMS AI Copilot**.\n\nSaya dapat membantu Anda mencari data **pelanggan**, mengecek status **tagihan & verifikasi pembayaran**, memeriksa **tiket gangguan**, serta memantau **OLT & jaringan** secara langsung.\n\nAda yang bisa saya bantu sekarang?",
                time: this.currentTime()
            }];
            this.saveMessages();
        },

        toggleChat() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.$nextTick(() => {
                    this.scrollToBottom();
                    if (this.$refs.inputField) {
                        this.$refs.inputField.focus();
                    }
                });
            }
        },

        currentTime() {
            const d = new Date();
            return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
        },

        saveMessages() {
            try {
                // Keep max 20 messages in session storage
                const slice = this.messages.slice(-20);
                sessionStorage.setItem('ims_ai_chat_messages', JSON.stringify(slice));
            } catch (e) {}
        },

        clearHistory() {
            if (confirm('Bersihkan riwayat percakapan AI?')) {
                this.addWelcomeMessage();
                this.scrollToBottom();
            }
        },

        async checkStatus() {
            try {
                const res = await fetch('{{ route("ai.status") }}');
                if (res.ok) {
                    const data = await res.json();
                    if (data.configured) {
                        this.engineMode = 'Gemini Flash';
                    } else {
                        this.engineMode = 'DB Engine';
                    }
                }
            } catch (e) {}
        },

        sendQuickPrompt(text) {
            this.userInput = text;
            this.sendMessage();
        },

        async sendMessage() {
            const text = this.userInput.trim();
            if (!text || this.isLoading) return;

            // Add user message
            this.messages.push({
                role: 'user',
                text: text,
                time: this.currentTime()
            });

            this.userInput = '';
            this.isLoading = true;
            this.saveMessages();
            this.$nextTick(() => this.scrollToBottom());

            try {
                // Build history for backend
                const historyPayload = this.messages.map(m => ({
                    role: m.role === 'user' ? 'user' : 'model',
                    text: m.text
                }));

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                const response = await fetch('{{ route("ai.chat") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify({
                        message: text,
                        history: historyPayload
                    })
                });

                const data = await response.json();

                if (response.ok && data.status === 'success') {
                    if (data.mode === 'gemini') {
                        this.engineMode = 'Gemini Flash';
                    } else if (data.mode === 'local_fallback') {
                        this.engineMode = 'Local Fallback';
                    }

                    this.messages.push({
                        role: 'assistant',
                        text: data.reply || 'Data berhasil diproses.',
                        time: this.currentTime()
                    });
                } else {
                    this.messages.push({
                        role: 'assistant',
                        text: '⚠️ Maaf, terjadi kendala: ' + (data.message || 'Server error'),
                        time: this.currentTime()
                    });
                }
            } catch (err) {
                this.messages.push({
                    role: 'assistant',
                    text: '⚠️ Gagal terhubung ke server IMS: ' + err.message,
                    time: this.currentTime()
                });
            } finally {
                this.isLoading = false;
                this.saveMessages();
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        scrollToBottom() {
            if (this.$refs.chatBox) {
                this.$refs.chatBox.scrollTop = this.$refs.chatBox.scrollHeight;
            }
        },

        copyText(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text);
                alert('Teks berhasil disalin ke clipboard!');
            }
        },

        renderMarkdown(rawText) {
            if (!rawText) return '';
            
            // Escape dangerous HTML tags
            let escaped = rawText
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Headers
            escaped = escaped.replace(/^### (.*$)/gim, '<h4 class="text-xs font-bold text-cyan-300 mt-2 mb-1">$1</h4>');
            escaped = escaped.replace(/^## (.*$)/gim, '<h3 class="text-sm font-bold text-cyan-200 mt-2.5 mb-1">$1</h3>');

            // Blockquotes (e.g. > tips)
            escaped = escaped.replace(/^\&gt; (.*$)/gim, '<blockquote>$1</blockquote>');

            // Bold & Italic
            escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong class="text-white font-semibold">$1</strong>');
            escaped = escaped.replace(/\*(.*?)\*/g, '<em class="text-slate-300">$1</em>');

            // Inline code `...`
            escaped = escaped.replace(/`([^`]+)`/g, '<code>$1</code>');

            // Unordered list items (- item)
            escaped = escaped.replace(/^\s*-\s+(.*$)/gim, '<li class="text-slate-200">$1</li>');

            // Table parsing (simple markdown table parser)
            if (escaped.includes('|')) {
                const lines = escaped.split('\n');
                let inTable = false;
                let tableHtml = '<div class="overflow-x-auto my-2"><table class="w-full"><tbody>';
                let newLines = [];

                for (let i = 0; i < lines.length; i++) {
                    const line = lines[i].trim();
                    if (line.startsWith('|') && line.endsWith('|')) {
                        // Skip separator row |---|---|
                        if (/^\|[\s\-:|]+\|$/.test(line)) {
                            continue;
                        }

                        const cells = line.split('|').slice(1, -1).map(c => c.trim());
                        const isHeader = !inTable && (i + 1 < lines.length && /^\|[\s\-:|]+\|$/.test(lines[i+1].trim()));

                        let row = '<tr>';
                        cells.forEach(c => {
                            if (isHeader) {
                                row += `<th>${c}</th>`;
                            } else {
                                row += `<td>${c}</td>`;
                            }
                        });
                        row += '</tr>';

                        if (!inTable) {
                            inTable = true;
                            newLines.push(tableHtml + row);
                        } else {
                            newLines.push(row);
                        }
                    } else {
                        if (inTable) {
                            inTable = false;
                            newLines.push('</tbody></table></div>');
                        }
                        newLines.push(line);
                    }
                }
                if (inTable) {
                    newLines.push('</tbody></table></div>');
                }
                escaped = newLines.join('\n');
            }

            // Convert newlines to breaks
            escaped = escaped.replace(/\n/g, '<br/>');

            // Clean multiple <br/> inside tables or lists
            escaped = escaped.replace(/<\/tr><br\/>/g, '</tr>');
            escaped = escaped.replace(/<\/table><\/div><br\/>/g, '</table></div>');
            escaped = escaped.replace(/<\/li><br\/>/g, '</li>');

            return escaped;
        }
    };
};
</script>

<div x-data="imsAiCopilot()" class="relative z-50">
    <!-- Floating Trigger Button -->
    <div class="fixed bottom-5 right-5 sm:bottom-6 sm:right-6 z-40 flex items-center gap-2">
        <button 
            type="button" 
            @click="toggleChat()" 
            class="group relative flex items-center gap-2.5 px-4 py-3 rounded-full bg-gradient-to-r from-cyan-600 via-teal-600 to-cyan-500 hover:from-cyan-500 hover:to-teal-500 text-white font-medium shadow-lg shadow-cyan-900/40 hover:shadow-cyan-500/30 transition-all duration-300 transform hover:scale-105 active:scale-95 border border-cyan-400/30 cursor-pointer"
            title="Buka IMS AI Copilot"
            aria-label="IMS AI Assistant">
            <!-- Pulsing indicator glow -->
            <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-400 border-2 border-slate-900"></span>
            </span>

            <!-- Sparkle / Robot Icon -->
            <svg class="w-5 h-5 text-cyan-100 group-hover:rotate-12 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>

            <span class="text-sm font-semibold tracking-wide">IMS AI</span>
            <span class="text-[10px] font-mono uppercase px-1.5 py-0.5 rounded bg-black/30 text-cyan-200 border border-cyan-300/20">Beta</span>
        </button>
    </div>

    <!-- Chat Modal / Window -->
    <div 
        x-show="isOpen" 
        x-cloak
        x-transition:enter="transition ease-out duration-250 transform"
        x-transition:enter-start="opacity-0 translate-y-6 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-6 scale-95"
        class="fixed bottom-20 right-3 sm:right-6 w-[calc(100vw-24px)] sm:w-[460px] h-[580px] max-h-[82vh] bg-slate-900/95 backdrop-blur-xl border border-cyan-500/30 rounded-2xl shadow-2xl shadow-black/70 flex flex-col overflow-hidden z-50 text-slate-100">
        
        <!-- Header -->
        <div class="px-4 py-3.5 bg-slate-950/80 border-b border-cyan-500/20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-500 to-teal-600 flex items-center justify-center text-white shadow-md shadow-cyan-500/30">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold tracking-tight text-white">IMS AI Copilot</h3>
                        <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-cyan-950 text-cyan-300 border border-cyan-700/40" x-text="engineMode"></span>
                    </div>
                    <p class="text-[11px] text-slate-400">Asisten Pintar NOC, Finance & Operasional</p>
                </div>
            </div>

            <div class="flex items-center gap-1">
                <!-- Clear Chat -->
                <button 
                    type="button" 
                    @click="clearHistory()" 
                    title="Bersihkan Percakapan"
                    class="p-1.5 text-slate-400 hover:text-cyan-300 hover:bg-slate-800/80 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
                <!-- Close Button -->
                <button 
                    type="button" 
                    @click="isOpen = false" 
                    class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800/80 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Chat Message Area -->
        <div x-ref="chatBox" class="flex-1 overflow-y-auto p-4 space-y-4 text-xs leading-relaxed ims-main-scroll">
            <template x-for="(msg, index) in messages" :key="index">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div 
                        :class="msg.role === 'user' 
                            ? 'bg-cyan-600 text-white rounded-2xl rounded-tr-sm px-4 py-2.5 max-w-[85%] shadow-md shadow-cyan-950/30' 
                            : 'bg-slate-800/90 border border-slate-700/80 text-slate-200 rounded-2xl rounded-tl-sm px-4 py-3 max-w-[92%] shadow-md shadow-black/40'"
                        class="relative group">
                        
                        <!-- Role Icon / Header for AI -->
                        <div x-show="msg.role === 'assistant'" class="flex items-center justify-between mb-1 pb-1 border-b border-slate-700/50 text-[10px] text-cyan-400 font-mono">
                            <span class="flex items-center gap-1 font-semibold">
                                <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 inline-block"></span>
                                IMS Copilot
                            </span>
                            <button 
                                type="button" 
                                @click="copyText(msg.text)" 
                                class="text-slate-400 hover:text-cyan-300 transition-colors"
                                title="Salin Teks">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </button>
                        </div>

                        <!-- Rendered Content -->
                        <div class="ai-content-body space-y-2 prose prose-invert prose-xs max-w-none break-words" x-html="renderMarkdown(msg.text)"></div>

                        <!-- Timestamp -->
                        <div class="text-[9px] mt-1 text-right" :class="msg.role === 'user' ? 'text-cyan-200' : 'text-slate-400'" x-text="msg.time"></div>
                    </div>
                </div>
            </template>

            <!-- Loading Indicator -->
            <div x-show="isLoading" class="flex justify-start">
                <div class="bg-slate-800/90 border border-cyan-500/30 rounded-2xl rounded-tl-sm px-4 py-3 text-slate-300 shadow-md flex items-center gap-2">
                    <span class="flex gap-1">
                        <span class="w-2 h-2 rounded-full bg-cyan-400 animate-bounce" style="animation-delay: 0ms"></span>
                        <span class="w-2 h-2 rounded-full bg-teal-400 animate-bounce" style="animation-delay: 150ms"></span>
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-bounce" style="animation-delay: 300ms"></span>
                    </span>
                    <span class="text-[11px] font-mono text-cyan-300 ml-1">Menganalisis sistem & database IMS...</span>
                </div>
            </div>
        </div>

        <!-- Quick Suggestions Chips -->
        <div class="px-3 py-2 bg-slate-950/60 border-t border-slate-800 flex items-center gap-1.5 overflow-x-auto ims-main-scroll whitespace-nowrap">
            <template x-for="chip in quickChips" :key="chip.label">
                <button 
                    type="button" 
                    @click="sendQuickPrompt(chip.prompt)" 
                    class="inline-flex items-center gap-1 text-[11px] px-2.5 py-1 rounded-full bg-slate-800/90 hover:bg-cyan-950 hover:text-cyan-300 hover:border-cyan-500/40 border border-slate-700/60 text-slate-300 transition-all flex-shrink-0 cursor-pointer">
                    <span x-text="chip.icon"></span>
                    <span x-text="chip.label"></span>
                </button>
            </template>
        </div>

        <!-- Input Bar -->
        <div class="p-3 bg-slate-950 border-t border-cyan-500/20">
            <form @submit.prevent="sendMessage()" class="flex items-center gap-2">
                <input 
                    type="text" 
                    x-model="userInput" 
                    x-ref="inputField"
                    :disabled="isLoading"
                    placeholder="Tanya pelanggan, tagihan, status OLT, atau tiket..." 
                    class="flex-1 bg-slate-900 border border-slate-700 focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 outline-none transition-all disabled:opacity-50"
                />
                <button 
                    type="submit" 
                    :disabled="isLoading || !userInput.trim()"
                    class="p-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 active:bg-cyan-700 text-white shadow-md shadow-cyan-900/40 disabled:opacity-40 disabled:cursor-not-allowed transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>
