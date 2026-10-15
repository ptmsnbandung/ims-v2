# Taste-Skill Rule (UI Frontend Craftsmanship & Anti-Slop Design)

Aturan taste-skill dirancang untuk mencegah tampilan antarmuka (UI) generik ala AI ("AI slop UI") dan memastikan setiap komponen frontend dirancang dengan standar estetika tinggi, elegan, dan profesional.

---

## 1. Tolak UI Generik (Reject Generic AI Slop UI)
- ❌ DILARANG: Warna primer kasar (`#0000ff`, `#00cc00`, `#ff0000`), gradien ungu klise khas AI, bayangan kasar (*harsh drop shadows*), atau teks vertikal terbalik/glitch.
- ❌ DILARANG: Template generik dengan kartu berjejalan tanpa ritme spasi yang proporsional.
- ✅ DIIZINKAN: Palet warna kurasi harmonis, gradien halus multi-stop dengan opacity bertingkat, dan kontur batas halus (`border-slate-800/80` atau `border-slate-200/80`).

---

## 2. Arah Visual & Karakter (Art Direction)
- Tentukan arah visual yang kohesif sesuai domain:
  - **NOC / Telemetri Jaringan:** Konsol gelap obsidian/slate (`bg-[#070e1b]` atau `bg-slate-900`), aksen neon emerald/cyan yang memendar (*subtle glow*), tipografi monospace presisi untuk angka/kecepatan/SN/IP.
  - **Manajemen Pelanggan / Form / Tabel:** Bersih, kontras tinggi, hierarki tipografi tegas, padding lega, dan status badge yang intuitif.

---

## 3. Tipografi & Hierarki Visual
- Gunakan hierarki ukuran font yang terukur (Display, Title, Body, Mono).
- Angka metrik, IP address, serial number, dan koordinat grafik WAJIB menggunakan `font-mono` agar sejajar dan mudah dibaca secara analitis.
- Hindari teks vertikal yang membingungkan atau membuat tata letak terjepit; utamakan label ringkas horizontal atau badge unit minimalis.

---

## 4. Keutuhan Layout & Responsivitas (Layout Integrity)
- Pastikan seluruh tag pembungkus grid/flex ditutup secara sempurna. Jangan pernah membiarkan elemen grafik terjepit menjadi kolom sempit karena tag yang bocor.
- Jaga rasio aspek grafik agar gelombang visual memiliki ruang vertikal dan horizontal yang proporsional (rasio ideal ~2.5:1 hingga ~3:1).
- Kartu legenda dan metrik harus terstruktur (misal grid 2 kolom simetris) sehingga tidak ada teks yang bertumpuk atau terpotong saat layar menyempit.
