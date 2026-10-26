# 🏢 Panduan Teknis Implementasi "Kantor AI" (n8n Multi-Agent Workspace)
**PT Media Solusi Network (MSN) — IMS-v2 Integration**

---

## 📌 Ringkasan Arsitektur
Sistem **Kantor AI** dirancang sebagai *Multi-Agent Workspace* menggunakan **n8n** sebagai orkestrator otomatisasi utama, terhubung langsung dengan basis data MySQL **IMS-v2** dan didukung oleh **Google Gemini AI**.

```
[ Pelanggan / WhatsApp / Telegram ]
               │
               ▼ (Webhook HTTPS via Cloudflare Tunnel)
     ┌──────────────────┐
     │ cloudflared      │ (Tanpa Port Forwarding / IP Publik)
     └─────────┬────────┘
               │ (Proxy internal)
               ▼
     ┌──────────────────┐       ┌──────────────────────┐
     │       n8n        │──────▶│ PostgreSQL 16        │ (State & Credentials n8n)
     │  (Agent Engine)  │       └──────────────────────┘
     └─────────┬────────┘
               │
      ┌────────┴────────────────────────┬─────────────────────────┐
      ▼                                 ▼                         ▼
┌───────────────┐              ┌────────────────┐       ┌───────────────────┐
│ MySQL IMS-v2  │              │ Google Gemini  │       │ WhatsApp Gateway  │
│ (Data Tagihan,│              │ (Analisis teks │       │ / Telegram Bot    │
│  Pelanggan,   │              │  & personifikasi│      │ (Notifikasi & CS) │
│  Log OLT)     │              │  pesan ramah)  │       │                   │
└───────────────┘              └────────────────┘       └───────────────────┘
```

---

## 📂 Struktur Direktori
```text
services/kantor-ai/
├── docker-compose.yml              # Stack n8n, PostgreSQL 16, & cloudflared
├── .env.example                    # Template variabel lingkungan
├── sql/
│   └── create_n8n_readonly_user.sql # Script SQL pembuatan user MySQL khusus
└── workflows/
    └── workflow_1_finance_billing_agent.json # Template n8n JSON siap import
```

---

## 🛠️ Langkah 1: Persiapan User MySQL IMS (Read-Only)

Untuk menjaga keamanan integritas data aplikasi IMS, n8n tidak boleh menggunakan akun `root`. Gunakan script SQL yang telah disediakan:

1. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`) atau terminal MySQL Anda.
2. Jalankan perintah di [create_n8n_readonly_user.sql](file:///c:/xampp/htdocs/ims2/services/kantor-ai/sql/create_n8n_readonly_user.sql):
   ```sql
   CREATE USER IF NOT EXISTS 'n8n_reader'@'%' IDENTIFIED BY 'PasswordAmanN8n2026!#';
   GRANT SELECT ON ims_v2.* TO 'n8n_reader'@'%';
   FLUSH PRIVILEGES;
   ```
3. Pastikan MySQL mendengarkan pada interface lokal/network (pada file `my.ini` XAMPP, pastikan `bind-address = 0.0.0.0` atau dikomentari).

---

## 🚀 Langkah 2: Menjalankan Docker Stack n8n

Pastikan **Docker Desktop** (untuk Windows) atau **Docker Engine** (untuk Linux) sudah terpasang dan aktif.

1. Buka PowerShell atau Terminal, lalu arahkan ke direktori Kantor AI:
   ```powershell
   cd c:\xampp\htdocs\ims2\services\kantor-ai
   ```

2. Buat file `.env` dari `.env.example`:
   ```powershell
   copy .env.example .env
   ```

3. Buka file `.env` dan sesuaikan parameter berikut:
   - `N8N_ENCRYPTION_KEY`: Masukkan string acak minimal 32 karakter unik.
   - `CLOUDFLARE_TUNNEL_TOKEN`: Masukkan token tunnel Cloudflare Anda (lihat Langkah 3).

4. Jalankan seluruh container di background:
   ```powershell
   docker compose up -d
   ```

5. Periksa status container:
   ```powershell
   docker compose ps
   ```
   *Anda akan melihat 3 container aktif: `kantor_ai_postgres`, `kantor_ai_n8n`, dan `kantor_ai_cloudflared`.*

6. Akses dashboard n8n lokal via browser:
   ```text
   http://localhost:5678
   ```
   *Pada kunjungan pertama, n8n akan meminta Anda membuat akun Admin/Owner.*

---

## 🌐 Langkah 3: Konfigurasi Cloudflare Tunnel (Tanpa IP Publik)

Agar webhook WhatsApp / Telegram dapat mengirim pesan langsung ke n8n di komputer/server kantor:

1. Masuk ke dashboard **Cloudflare Zero Trust** (`dash.teams.cloudflare.com`).
2. Masuk ke menu **Networks** ➡️ **Tunnels** ➡️ Klik **Create a Tunnel**.
3. Pilih konektor **Cloudflared** dan beri nama misalnya: `kantor-ai-ptmsn`.
4. Salin **Tunnel Token** yang disediakan, lalu masukkan ke file `.env` pada variabel:
   ```env
   CLOUDFLARE_TUNNEL_TOKEN=eyJhIjoi...token_anda...
   ```
5. Pada tab **Public Hostnames** di Cloudflare:
   - **Subdomain:** misal `ai-office`
   - **Domain:** domain kantor Anda (misal `ptmsn.net`)
   - **Service Type:** `HTTP`
   - **URL:** `n8n:5678` *(karena n8n dan cloudflared berada dalam 1 Docker network `kantor_ai_net`)*
6. Simpan konfigurasi. Sekarang n8n Anda sudah online dengan HTTPS valid di `https://ai-office.ptmsn.net/`.

---

## 🔌 Langkah 4: Menghubungkan n8n ke MySQL IMS

1. Di dashboard n8n, buka menu **Credentials** ➡️ **Add Credential**.
2. Cari dan pilih **MySQL**.
3. Isi parameter koneksi:
   - **Host:** `host.docker.internal` *(ini adalah alamat host server dari dalam container Docker)*
   - **Port:** `3306`
   - **Database:** `ims_v2`
   - **User:** `n8n_reader`
   - **Password:** `PasswordAmanN8n2026!#`
   - **SSL:** `false` (Disabled)
4. Klik **Save & Test Connection**. Jika muncul pesan *"Connection tested successfully"*, n8n telah berhasil terhubung ke basis data IMS.

---

## 📥 Langkah 5: Import Workflow 1 (Finance Billing Agent)

1. Di dashboard n8n, buat workflow baru atau klik tombol **Add Workflow**.
2. Klik tombol menu (tiga titik di kanan atas) ➡️ **Import from File...** (atau salin isi file [workflow_1_finance_billing_agent.json](file:///c:/xampp/htdocs/ims2/services/kantor-ai/workflows/workflow_1_finance_billing_agent.json) lalu tekan `Ctrl + V` di kanvas n8n).
3. Konfigurasi kredensial pada node:
   - Klik ganda pada node **MySQL - Tarik Tagihan Belum Lunas**, lalu hubungkan dengan credential MySQL yang dibuat pada Langkah 4.
   - Klik ganda pada node **Gemini AI - Buat Pesan Ramah**, ganti parameter `MASUKKAN_GEMINI_API_KEY_ANDA` dengan API Key Google Gemini Anda (atau masukkan variabel `GEMINI_API_KEY`).
   - Klik ganda pada node **Kirim Notifikasi Telegram / WA**, sesuaikan token bot Telegram dan chat ID Anda, atau ubah endpoint ke WA Gateway Anda.
4. Klik **Test step** atau jalankan manual untuk memastikan data tagihan berhasil ditarik dan dirangkum oleh AI.
5. Aktifkan saklar **Active** di pojok kanan atas agar cron berjalan otomatis tiap hari.

---

## 🗺️ Roadmap Workflow Divisi Selanjutnya

* **Workflow 2: NOC Sentry Agent**
  - Trigger: Polling tiap 5 menit mengecek tabel `activity_logs` atau ping router.
  - Action: Jika router/OLT gagal merespons, Gemini AI mendiagnosis kemungkinan penyebabnya lalu mengirim alert terstruktur ke grup Telegram tim NOC.
* **Workflow 3: Customer Service & Helpdesk Agent**
  - Trigger: Webhook pesan masuk WhatsApp dari pelanggan.
  - Action: Gemini membaca riwayat langganan dari MySQL `view_batchjob`, lalu menjawab solusi teknis secara ramah. Jika ada indikasi kabel putus/redaman tinggi, AI otomatis menembak endpoint pembuatan tiket di IMS.
