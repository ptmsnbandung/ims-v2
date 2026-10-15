# Ponytail Rule (Lazy Senior Developer Mode)

Prinsip utama: **"The best code is the code never written"** (Kode terbaik adalah kode yang tidak perlu ditulis). Selesaikan seluruh permasalahan dengan perubahan kode seminimal mungkin, tanpa over-engineering, tanpa abstraksi berlebihan, dan selalu prioritaskan efisiensi kerja.

---

## 1. Sebelum Menulis Kode (Before You Write)
1. **Analisis Tepat Sasaran:** Baca task dan telusuri hanya file/fungsi yang relevan.
2. **Pahami Dampak (Scope):** Ketahui semua pemanggil (*callers*), konfigurasi, dan potensi efek samping terhadap data/pengguna.
3. **Fokus pada Inti:** Fitur ekstra di luar permintaan pengguna bukanlah bagian dari *scope*. Tolak kompleksitas yang tidak diminta (*YAGNI - You Aren't Gonna Need It*).

---

## 2. Tangga Keputusan Kode Paling Minimal (Decision Ladder)
Gunakan opsi pertama yang sepenuhnya bekerja:
1. **Apakah perlu dibuat?** Jika ada opsi/fleksibilitas yang tidak diminta, lewati dan sebutkan dalam satu baris.
2. **Sudah ada di codebase?** Pakai helper, service, method, model, atau komponen yang sudah ada di repository dengan cara kerja yang konsisten.
3. **Fitur bawaan platform / standard library?** Gunakan fungsi bawaan framework (Laravel / PHP / Blade / Vanilla JS / CSS) sebelum membuat custom logic.
4. **Dependency yang sudah terpasang?** Manfaatkan package yang sudah ada di `composer.json` / `package.json`. Jangan pernah menambahkan dependensi baru hanya untuk beberapa baris kode.
5. **Bisa satu baris ringkas?** Jika satu baris mudah dipahami oleh pembaca, buat satu baris.
6. **Jika tidak:** Tulis kode seminimal mungkin yang bekerja dengan benar dan andal.

---

## 3. Prinsip Kerja & Batasan (Principles & Guardrails)
- **Malas solusinya, tapi teliti pekerjaannya:** Sederhanakan implementasi, tetapi pastikan seluruh pemanggil (*callers*), error handling, dan alur data diperbaiki tuntas.
- **Hindari Bloat:** Jangan membuat wrapper baru, DTO baru, helper baru, konfigurasi "untuk masa depan", atau tipe data berbelit jika platform sudah menyediakannya langsung.
- **Menghapus kode lebih baik daripada menambah kode (*Deletion beats addition*).**
- **Jangan pernah memotong:**
  - Validasi keamanan (*security / trust boundaries*).
  - Penanganan error pencegah kehilangan data (*data loss prevention*).
  - Kebutuhan teknis riil hardware/perangkat (misal kalibrasi OLT/Router).
  - Hal-hal spesifik yang diminta secara eksplisit oleh pengguna.

---

## 4. Format Laporan Akhir (Accountability)
Setiap kali menyelesaikan pekerjaan:
- Tuliskan ringkasan singkat & to-the-point mengenai perubahan yang dibuat.
- Akhiri dengan 1–2 baris: apa yang dilewati/tidak diperiksa, dan risiko yang wajib diketahui pengguna (jika ada).
