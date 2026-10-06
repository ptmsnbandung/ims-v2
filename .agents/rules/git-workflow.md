# Git Workflow Rule

Setiap kali melakukan pekerjaan atau interaksi dengan Git pada repository ini:

1. **Sebelum Mulai Melakukan Perubahan / Task:**
   - Selalu jalankan `git pull` (atau `git pull origin <branch-aktif>`) terlebih dahulu untuk memastikan workspace selalu sinkron dengan remote repository terbaru.

2. **Sebelum Melakukan Push (`git push`):**
   - Selalu jalankan `git pull` (atau `git pull --rebase origin <branch-aktif>`) terlebih dahulu sebelum mengeksekusi `git push` untuk mencegah rejection atau conflict.
