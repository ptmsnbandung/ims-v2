---
name: taste-skill
description: Frontend UI craftsmanship and anti-slop design standards for building premium, modern, and elegant web interfaces.
---

# Taste-Skill (UI Frontend Craftsmanship)

Skill ini memandu AI agent dalam merancang antarmuka pengguna (UI) modern berstandar visual tinggi, elegan, bebas dari tampilan generik template AI ("anti-slop"), dan terintegrasi mulus dengan Tailwind CSS, Alpine.js, dan Blade templates.

---

## 1. Filosofi Desain
* **Anti-Slop:** Tolak UI yang terlihat seperti template bawaan atau AI generik. Gunakan kurasi warna yang elegan, gradien berkedalaman, tipografi terukur, dan spasi yang proporsional.
* **Karakter & Konteks:** Setiap modul harus mencerminkan karakternya. Untuk modul monitoring jaringan/NOC (OLT, PON, ONU), terapkan gaya konsol telemetri modern (gelap berwibawa, garis grid halus, kurva neon berpendar). Untuk modul manajemen pelanggan/administrasi, terapkan desain clean enterprise yang tajam dan nyaman dibaca.
* **Mikro-Animasi Berbobot:** Gunakan titik status berkedip (*pulsing indicators*), transisi halus pada hover/focus, dan feedback visual yang responsif tanpa berlebihan.

---

## 2. Standar Visual Komponen

### A. Palet Warna & Efek Pencahayaan
* **Inbound / Download / Rx:** Emerald Glow (`#10b981` gradien fading dari opacity 0.55 ke 0.0) dengan garis stroke `#10b981`.
* **Outbound / Upload / Tx:** Electric Sky Blue (`#38bdf8`) dengan efek glow halus (`feDropShadow`).
* **Latar Belakang Telemetri:** Obsidian / Deep Slate (`#070e1b` atau `bg-slate-900`) dengan border `border-slate-800`.
* **Grid Lines:** Gunakan warna lembut (`#1e293b` stroke-dasharray="3,3") dengan garis baseline yang tegas (`#334155`).

### B. Tipografi & Data Telemetri
* Seluruh data analitis (kecepatan Mbps, redaman dBm, serial number, IP, port, timestamps) **wajib** menggunakan `font-mono`.
* Sumbu Y dan X grafik harus bersih, tidak menggunakan font vertikal terbalik, dan disejajarkan dengan rapi menggunakan lebar kolom tetap.

### C. Kartu Metrik & Legenda
* Terapkan kartu metrik terstruktur (seperti grid 2 kolom simetris untuk Inbound & Outbound).
* Tampilkan hierarki informasi yang jelas:
  1. Header/arah dengan titik status berkedip.
  2. Nilai real-time (*Current*) tebal dan kontras.
  3. Nilai analitik agregat (*Max* dan *Avg*) di baris bawah.

---

## 3. Checklist Sebelum Selesai
1. Apakah tag pembungkus HTML (grid, flex, svg) tertutup sempurna tanpa kebocoran struktur?
2. Apakah grafik memiliki rasio aspek yang proporsional dan tidak tertekan (*squashed*)?
3. Apakah teks terbaca jelas di berbagai ukuran kontainer tanpa bertumpuk atau terpotong?
4. Apakah tampilan terasa premium dan profesional saat dilihat sekilas pertama kali?
