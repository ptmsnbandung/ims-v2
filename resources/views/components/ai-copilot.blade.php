{{-- IMS2 AI Copilot Floating Assistant Widget --}}
<style>
/* Robust CSS for IMS AI Floating Widget */
.ims-ai-trigger-container {
    position: fixed !important;
    bottom: 22px !important;
    right: 22px !important;
    z-index: 9999999 !important;
    display: flex !important;
    align-items: center !important;
}

.ims-ai-trigger-btn {
    display: inline-flex !important;
    align-items: center !important;
    gap: 10px !important;
    padding: 7px 10px 7px 18px !important;
    border-radius: 9999px !important;
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.96) 0%, rgba(30, 41, 59, 0.94) 100%) !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
    color: #ffffff !important;
    border: 1px solid rgba(56, 189, 248, 0.35) !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.55), 0 0 15px rgba(56, 189, 248, 0.2) !important;
    cursor: pointer !important;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    user-select: none !important;
}

.ims-ai-trigger-btn:hover {
    transform: translateY(-2px) scale(1.03) !important;
    border-color: rgba(56, 189, 248, 0.75) !important;
    box-shadow: 0 14px 30px -4px rgba(0, 0, 0, 0.7), 0 0 24px rgba(56, 189, 248, 0.4) !important;
    background: linear-gradient(135deg, rgba(15, 23, 42, 1) 0%, rgba(30, 41, 59, 0.98) 100%) !important;
}

.ims-ai-trigger-btn:active {
    transform: scale(0.96) !important;
}

.ims-ai-trigger-text {
    font-weight: 700 !important;
    font-size: 13.5px !important;
    letter-spacing: 0.03em !important;
    color: #ffffff !important;
    display: inline-block !important;
}

.ims-ai-trigger-logo {
    width: 28px !important;
    height: 28px !important;
    object-fit: contain !important;
    display: block !important;
    filter: drop-shadow(0 2px 6px rgba(56, 189, 248, 0.35)) !important;
}

.ims-ai-modal-box {
    position: fixed !important;
    bottom: 76px !important;
    right: 18px !important;
    width: 440px !important;
    max-width: calc(100vw - 36px) !important;
    height: 580px !important;
    max-height: calc(100vh - 95px) !important;
    z-index: 9999999 !important;
    background: rgba(8, 18, 30, 0.98) !important;
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
    border: 1px solid rgba(6, 182, 212, 0.35) !important;
    border-radius: 20px !important;
    box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.85), 0 0 30px rgba(6, 182, 212, 0.2) !important;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    color: #e2e8f0;
}

.ims-ai-modal-box[style*="display: none"] {
    display: none !important;
}

.ims-ai-header {
    flex-shrink: 0 !important;
    height: 60px !important;
    padding: 0 16px !important;
    background: rgba(2, 6, 23, 0.95) !important;
    border-bottom: 1px solid rgba(6, 182, 212, 0.25) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
}

.ims-ai-icon-btn {
    width: 32px !important;
    height: 32px !important;
    border-radius: 8px !important;
    background: rgba(255, 255, 255, 0.06) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    color: #94a3b8 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
}

.ims-ai-icon-btn:hover {
    background: rgba(255, 255, 255, 0.15) !important;
    color: #ffffff !important;
}

.ims-ai-icon-btn.close-btn:hover {
    background: rgba(239, 68, 68, 0.25) !important;
    border-color: rgba(239, 68, 68, 0.4) !important;
    color: #fca5a5 !important;
}

.ims-ai-msg-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.ims-ai-chat-area {
    flex: 1 1 auto !important;
    min-height: 0 !important;
    overflow-y: auto !important;
    padding: 16px !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 12px !important;
    font-size: 12px !important;
    line-height: 1.6 !important;
    scrollbar-width: thin !important;
    scrollbar-color: rgba(56, 189, 248, 0.3) transparent !important;
}

.ims-ai-chat-area::-webkit-scrollbar {
    width: 5px !important;
}
.ims-ai-chat-area::-webkit-scrollbar-track {
    background: transparent !important;
}
.ims-ai-chat-area::-webkit-scrollbar-thumb {
    background: rgba(56, 189, 248, 0.3) !important;
    border-radius: 9999px !important;
}
.ims-ai-chat-area::-webkit-scrollbar-thumb:hover {
    background: rgba(56, 189, 248, 0.6) !important;
}

.ims-ai-chips-container {
    flex-shrink: 0 !important;
    height: 46px !important;
    max-height: 46px !important;
    padding: 8px 12px !important;
    background: rgba(2, 6, 23, 0.8) !important;
    border-top: 1px solid rgba(51, 65, 85, 0.6) !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    overflow-x: auto !important;
    overflow-y: hidden !important;
    white-space: nowrap !important;
    scrollbar-width: none !important;
    -ms-overflow-style: none !important;
}

.ims-ai-chips-container::-webkit-scrollbar,
.ims-ai-chips-container::-webkit-scrollbar-button,
.ims-ai-chips-container::-webkit-scrollbar-track,
.ims-ai-chips-container::-webkit-scrollbar-thumb {
    display: none !important;
    width: 0 !important;
    height: 0 !important;
}

.ims-ai-chip {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    height: 30px !important;
    max-height: 30px !important;
    line-height: 30px !important;
    padding: 0 12px !important;
    border-radius: 9999px !important;
    background: rgba(15, 23, 42, 0.85) !important;
    border: 1px solid rgba(51, 65, 85, 0.8) !important;
    color: #cbd5e1 !important;
    font-size: 11px !important;
    font-weight: 500 !important;
    cursor: pointer !important;
    flex-shrink: 0 !important;
    transition: all 0.2s ease !important;
    user-select: none !important;
}

.ims-ai-chip:hover {
    background: rgba(6, 182, 212, 0.18) !important;
    border-color: rgba(6, 182, 212, 0.45) !important;
    color: #38bdf8 !important;
    transform: translateY(-1px) !important;
}

.ims-ai-input-bar {
    flex-shrink: 0 !important;
    padding: 12px 14px !important;
    background: #020617 !important;
    border-top: 1px solid rgba(6, 182, 212, 0.25) !important;
}

/* Styling markdown tables & formatting inside AI Chat */
.ai-content-body table {
    width: 100%;
    margin: 0.5rem 0;
    border-collapse: collapse;
    font-size: 11px;
}
.ai-content-body th, .ai-content-body td {
    border: 1px solid rgba(51, 65, 85, 0.8);
    padding: 5px 8px;
    text-align: left;
}
.ai-content-body th {
    background: rgba(15, 23, 42, 0.9);
    color: #38bdf8;
    font-weight: 600;
}
.ai-content-body code {
    background: rgba(15, 23, 42, 0.85);
    color: #22d3ee;
    padding: 1px 5px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 10.5px;
    border: 1px solid rgba(6, 182, 212, 0.2);
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
        engineMode: 'AI',
        quickChips: [
            { icon: '📊', label: 'Rekap Hari Ini', prompt: 'Rekap operasional IMS2 hari ini' },
            { icon: '💳', label: 'Tagihan Pending', prompt: 'Cek tagihan pending verifikasi' },
            { icon: '🌐', label: 'Status OLT', prompt: 'Status OLT dan jaringan' },
            { icon: '🎫', label: 'Tiket Open', prompt: 'Cek tiket gangguan yang masih open' },
            { icon: '👥', label: 'Cari Pelanggan', prompt: 'Cek pelanggan aktif' }
        ],
        messages: [],

        init() {
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

        closeChat() {
            this.isOpen = false;
        },

        currentTime() {
            const d = new Date();
            return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
        },

        saveMessages() {
            try {
                const slice = this.messages.slice(-25);
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
                        this.engineMode = 'AI';
                    } else {
                        this.engineMode = 'Offline';
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
                        'X-CSRF-TOKEN': csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        message: text,
                        history: historyPayload
                    })
                });

                if (response.status === 419) {
                    this.messages.push({
                        role: 'assistant',
                        text: '⚠️ Sesi aplikasi telah kedaluwarsa. Silakan muat ulang (refresh) halaman untuk melanjutkan percakapan.',
                        time: this.currentTime()
                    });
                    return;
                }

                if (response.status === 504 || response.status === 502) {
                    this.messages.push({
                        role: 'assistant',
                        text: '⚠️ Waktu permintaan habis (Gateway Timeout). Server atau AI memerlukan waktu lebih lama untuk memproses query. Silakan coba ajukan pertanyaan yang lebih ringkas/spesifik.',
                        time: this.currentTime()
                    });
                    return;
                }

                let data = null;
                try {
                    data = await response.json();
                } catch (jsonErr) {
                    throw new Error('Server mengembalikan respons non-JSON (HTTP ' + response.status + '). Kemungkinan terjadi batas waktu (timeout) pada web server.');
                }

                if (response.ok && data && data.status === 'success') {
                    if (data.mode === 'gemini') {
                        this.engineMode = 'AI';
                    } else {
                        this.engineMode = 'Offline';
                    }

                    this.messages.push({
                        role: 'assistant',
                        text: data.reply || 'Data berhasil diproses.',
                        time: this.currentTime()
                    });
                } else {
                    this.messages.push({
                        role: 'assistant',
                        text: '⚠️ Maaf, terjadi kendala: ' + (data?.message || ('HTTP ' + response.status)),
                        time: this.currentTime()
                    });
                }
            } catch (err) {
                let friendlyMsg = err.message || 'Koneksi terputus';
                if (friendlyMsg.includes('Failed to fetch') || friendlyMsg.includes('NetworkError')) {
                    friendlyMsg = 'Koneksi ke server terputus atau waktu permintaan habis (timeout). Silakan periksa jaringan server atau coba kirim ulang pertanyaan.';
                }
                this.messages.push({
                    role: 'assistant',
                    text: '⚠️ ' + friendlyMsg,
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
            
            let escaped = rawText
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            escaped = escaped.replace(/^### (.*$)/gim, '<h4 style="color:#38bdf8; font-weight:700; margin:8px 0 4px 0;">$1</h4>');
            escaped = escaped.replace(/^## (.*$)/gim, '<h3 style="color:#7dd3fc; font-weight:700; margin:10px 0 4px 0;">$1</h3>');
            escaped = escaped.replace(/^\&gt; (.*$)/gim, '<blockquote>$1</blockquote>');
            escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong style="color:#ffffff; font-weight:600;">$1</strong>');
            escaped = escaped.replace(/\*(.*?)\*/g, '<em style="color:#cbd5e1;">$1</em>');
            escaped = escaped.replace(/`([^`]+)`/g, '<code>$1</code>');
            escaped = escaped.replace(/^\s*-\s+(.*$)/gim, '<li style="color:#e2e8f0; margin-left:14px;">$1</li>');

            if (escaped.includes('|')) {
                const lines = escaped.split('\n');
                let inTable = false;
                let tableHtml = '<div style="overflow-x:auto; margin:8px 0;"><table style="width:100%; border-collapse:collapse;"><tbody>';
                let newLines = [];

                for (let i = 0; i < lines.length; i++) {
                    const line = lines[i].trim();
                    if (line.startsWith('|') && line.endsWith('|')) {
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

            escaped = escaped.replace(/\n/g, '<br/>');
            escaped = escaped.replace(/<\/tr><br\/>/g, '</tr>');
            escaped = escaped.replace(/<\/table><\/div><br\/>/g, '</table></div>');
            escaped = escaped.replace(/<\/li><br\/>/g, '</li>');

            return escaped;
        }
    };
};
</script>

<div x-data="imsAiCopilot()" style="position: static !important;">
    <!-- Floating Trigger Button -->
    <div class="ims-ai-trigger-container">
        <button 
            type="button" 
            @click.stop="toggleChat()" 
            class="ims-ai-trigger-btn"
            title="Buka / Tutup IMS AI Copilot"
            aria-label="IMS AI Assistant">
            <span class="ims-ai-trigger-text">IMS AI</span>
            <img src="{{ asset('assets/images/msnai.png') }}" alt="IMS AI" class="ims-ai-trigger-logo">
        </button>
    </div>

    <!-- Chat Modal / Window -->
    <div 
        x-show="isOpen" 
        x-cloak
        @click.outside="closeChat()"
        @keydown.escape.window="closeChat()"
        x-transition:enter="transition ease-out duration-200 transform"
        x-transition:enter-start="opacity-0 translate-y-6 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150 transform"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        :style="!isOpen ? 'display: none !important;' : 'display: flex !important;'"
        class="ims-ai-modal-box">
        
        <!-- Header -->
        <div class="ims-ai-header">
            <div style="display:flex; align-items:center; gap:12px;">
                <img src="{{ asset('assets/images/msnai.png') }}" alt="IMS AI" style="width:36px; height:36px; object-fit:contain; display:block; filter: drop-shadow(0 2px 8px rgba(6, 182, 212, 0.4));">
                <div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <h3 style="font-size:14px; font-weight:700; color:#ffffff; margin:0; line-height:1.2;">IMS AI Copilot</h3>
                        <span style="font-size:10px; font-family:monospace; padding:1px 6px; border-radius:4px; background:#082f49; color:#38bdf8; border:1px solid rgba(56, 189, 248, 0.35);" x-text="engineMode"></span>
                    </div>
                    <p style="font-size:11px; color:#94a3b8; margin:2px 0 0 0;">Asisten Pintar NOC, Finance & Operasional</p>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:6px;">
                <!-- Clear Chat Button -->
                <button 
                    type="button" 
                    @click.stop="clearHistory()" 
                    class="ims-ai-icon-btn"
                    title="Bersihkan Percakapan">
                    <svg style="width:16px; height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
                <!-- Close Button -->
                <button 
                    type="button" 
                    @click.stop="closeChat()" 
                    class="ims-ai-icon-btn close-btn"
                    title="Tutup AI Chat (Esc)">
                    <svg style="width:18px; height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Chat Message Area -->
        <div x-ref="chatBox" class="ims-ai-chat-area">
            <template x-for="(msg, index) in messages" :key="index">
                <div :style="msg.role === 'user' ? 'display:flex; justify-content:flex-end;' : 'display:flex; justify-content:flex-start;'">
                    <div 
                        :style="msg.role === 'user' 
                            ? 'background:#0891b2; color:#ffffff; border-radius:16px 16px 2px 16px; padding:10px 14px; max-width:85%; box-shadow:0 4px 12px rgba(8,145,178,0.3);' 
                            : 'background:rgba(15,23,42,0.92); border:1px solid rgba(51,65,85,0.85); color:#e2e8f0; border-radius:16px 16px 16px 2px; padding:12px 14px; max-width:92%; box-shadow:0 4px 16px rgba(0,0,0,0.4);'"
                        style="position:relative;">
                        
                        <!-- Role Icon / Header for AI -->
                        <div class="ims-ai-msg-head" x-show="msg.role === 'assistant'" style="margin-bottom:6px; padding-bottom:4px; border-bottom:1px solid rgba(51,65,85,0.5); font-size:10px; color:#38bdf8; font-family:monospace;">
                            <span style="display:flex; align-items:center; gap:6px; font-weight:600;">
                                <img src="{{ asset('assets/images/msnai.png') }}" alt="IMS AI" style="width:16px; height:16px; object-fit:contain;">
                                IMS Copilot
                            </span>
                            <button 
                                type="button" 
                                @click="copyText(msg.text)" 
                                style="color:#94a3b8; background:none; border:none; cursor:pointer;"
                                title="Salin Teks">
                                <svg style="width:14px; height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </button>
                        </div>

                        <!-- Rendered Content -->
                        <div class="ai-content-body" style="word-break:break-word;" x-html="renderMarkdown(msg.text)"></div>

                        <!-- Timestamp -->
                        <div :style="msg.role === 'user' ? 'color:#cffafe;' : 'color:#64748b;'" style="font-size:9px; margin-top:4px; text-align:right;" x-text="msg.time"></div>
                    </div>
                </div>
            </template>

            <!-- Loading Indicator -->
            <div x-show="isLoading" style="display:flex; justify-content:flex-start;">
                <div style="background:rgba(15,23,42,0.95); border:1px solid rgba(6,182,212,0.35); border-radius:16px 16px 16px 2px; padding:10px 14px; display:flex; align-items:center; gap:8px;">
                    <span style="display:inline-flex; gap:4px;">
                        <span style="width:6px; height:6px; border-radius:9999px; background:#22d3ee;"></span>
                        <span style="width:6px; height:6px; border-radius:9999px; background:#2dd4bf;"></span>
                        <span style="width:6px; height:6px; border-radius:9999px; background:#34d399;"></span>
                    </span>
                    <span style="font-size:11px; font-family:monospace; color:#38bdf8;">Sedang menganalisis database IMS...</span>
                </div>
            </div>
        </div>

        <!-- Quick Suggestions Chips (Terkontrol & Horizontal) -->
        <div class="ims-ai-chips-container">
            <template x-for="chip in quickChips" :key="chip.label">
                <button 
                    type="button" 
                    @click.stop="sendQuickPrompt(chip.prompt)" 
                    class="ims-ai-chip">
                    <span x-text="chip.icon"></span>
                    <span x-text="chip.label"></span>
                </button>
            </template>
        </div>

        <!-- Input Bar -->
        <div class="ims-ai-input-bar">
            <form @submit.prevent="sendMessage()" style="display:flex; align-items:center; gap:8px; margin:0;">
                <input 
                    type="text" 
                    x-model="userInput" 
                    x-ref="inputField"
                    :disabled="isLoading"
                    placeholder="Tanya pelanggan, tagihan, status OLT, atau tiket..." 
                    style="flex:1; background:#0f172a; border:1px solid #334155; border-radius:10px; padding:10px 12px; font-size:12px; color:#ffffff; outline:none;"
                />
                <button 
                    type="submit" 
                    :disabled="isLoading || !userInput.trim()"
                    style="padding:10px 14px; border-radius:10px; background:linear-gradient(135deg, #0891b2, #0d9488); color:#fff; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                    <svg style="width:16px; height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>
