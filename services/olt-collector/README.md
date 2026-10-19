# IMS OLT & PON Telemetry Collector (Golang Engine)

Engine telemetri background berkinerja tinggi berbasis **Golang** untuk mengambil data traffic port GPON OLT, status sistem hardware, dan redaman ONU secara non-blocking dan ultra-cepat.

---

### Fitur Utama:
1. **High Concurrency & Non-Blocking**: Menggunakan Goroutines untuk membaca buffer Telnet OLT dalam hitungan milidetik tanpa membebani PHP-FPM / Apache.
2. **In-Memory TTL Cache (20 Detik)**: Mencegah spam query berulang ke hardware OLT fisik ketika banyak pengguna membuka dashboard NOC secara bersamaan.
3. **Seamless Failover & Fallback**:
   - Jika service Go ini aktif, Laravel IMS otomatis menggunakannya (waktu respons berkurang dari ~4-5 detik menjadi < 100ms).
   - Jika service Go ini tidak dijalankan / mati, Laravel IMS secara otomatis dan aman kembali menggunakan koneksi PHP bawaan (*zero downtime / zero error*).
4. **Zero Frontend Breaking Change**: Seluruh tampilan dashboard NOC, grafik MRTG, tabel PON, dan modal ONU tetap 100% identik tanpa perubahan UI.

---

### Cara Menjalankan:

#### Di Windows:
Cukup double-click `start.bat` atau jalankan via terminal:
```cmd
cd services\olt-collector
start.bat
```

#### Di Linux / Production Server:
```bash
cd services/olt-collector
chmod +x start.sh
./start.sh
```

Atau pasang sebagai `systemd` service:
```ini
[Unit]
Description=IMS OLT Telemetry Collector
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/ims2/services/olt-collector
ExecStart=/var/www/ims2/services/olt-collector/olt-collector
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

---

### Endpoint API Internal:
- `GET /health`: Cek status daemon
- `POST /api/olt/traffic`: Ambil traffic port GPON OLT 1..16 dan status processor/optik
- `POST /api/olt/onus`: Ambil status ONU fisik (SN, status LOS/working, redaman Rx dBm) pada port PON tertentu
