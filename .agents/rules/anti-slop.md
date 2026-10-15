# Anti-Slop Rule (Clean Code & Zero AI Bloat)

Aturan anti-slop dirancang untuk membasmi "AI slop" — kode bertele-tele, over-engineered, penuh komentar basa-basi, dan lapisan abstraksi palsu yang sering dihasilkan oleh LLM.

---

## 1. Prinsip Utama (Core Tenets)
1. **No Wrapper & Abstraction Bloat:** Jangan membungkus fungsi native framework ke dalam 3 lapisan helper/service/DTO baru tanpa kebutuhan arsitektural yang jelas. Logika langsung dan modular lebih unggul daripada abstraksi prematur.
2. **Hapus Komentar Basa-Basi (Ban Conversational Comments):**
   - ❌ DILARANG: Komentar yang hanya menerjemahkan sintaks ke bahasa manusia (contoh: `// Loop through users`, `// Return response as JSON`, `// Set variable x to true`).
   - ✅ DIIZINKAN: Komentar yang menjelaskan *alasan* (WHY), batasan bisnis yang tidak lazim, atau keanehan perangkat keras (contoh: quirk SNMP ZTE/Huawei).
3. **No Fake Fallbacks & Swallowed Errors:**
   - Dilarang membuat blok `try { ... } catch (e) { /* diam */ }` yang menyembunyikan bug nyata.
   - Jangan menyuntikkan data palsu/default bohongan hanya agar kode tidak melempar exception saat terjadi kegagalan sistem.
4. **Directness & Deletion:**
   - Kode terpendek dan paling langsung yang bekerja dengan benar adalah pemenangnya.
   - Selalu bersihkan dead code, percabangan mubazir, dan variabel tak terpakai saat menyentuh suatu berkas.
5. **High Signal-to-Noise Ratio:**
   - Setiap baris kode yang ditulis harus memiliki tujuan fungsional nyata. Jika bisa diselesaikan dengan 5 baris fungsi bawaan Laravel/PHP/JS, jangan tulis 30 baris custom logic.
