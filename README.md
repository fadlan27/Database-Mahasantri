# Database Mahasantri & EduCal Pro

## Info Proyek

**URL**: https://github.com/fadlan27/Database-Mahasantri

## Apa ini?

Ini adalah **Sistem Manajemen Santri** yang komprehensif untuk Pondok Pesantren, dilengkapi dengan **EduCal Pro**—kalender akademik canggih dengan dukungan tanggal Hijriah, penjadwalan berbasis prioritas, dan manajemen data santri (Biodata, Pelanggaran, Prestasi).

## Teknologi apa yang digunakan?

Proyek ini dibangun menggunakan teknologi standar industri yang handal:

- **PHP 8.x** (Backend Native)
- **MySQL / MariaDB** (Database)
- **Tailwind CSS** (Styling Modern)
- **Vanilla JavaScript** (Frontend Interaktif)
- **Lucide Icons** (Aset UI)

## Bagaimana cara menjalankannya di lokal?

Anda memerlukan lingkungan server lokal seperti **Laragon**, **XAMPP**, atau **MAMP**.

**Ikuti langkah-langkah berikut:**

```sh
# Langkah 1: Clone repositori ini ke dalam folder root web Anda (misal: c:/laragon/www)
git clone https://github.com/fadlan27/Database-Mahasantri.git

# Langkah 2: Masuk ke direktori proyek
cd "Database Mahasantri"

# Langkah 3: Pengaturan Database
# Buka manajer database Anda (phpMyAdmin/HeidiSQL).
# Buat database baru bernama 'mahasantri_db' (atau sesuaikan dengan file config).
# Import struktur/data SQL yang disediakan.

# Langkah 4: Jalankan
# Buka browser Anda dan kunjungi: http://database-mahasantri.test
# (Atau http://localhost/Database-Mahasantri tergantung pengaturan Anda)
```

## Bagaimana cara mengedit kode ini?

**Gunakan IDE pilihan Anda**

Kami merekomendasikan menggunakan **VS Code** dengan ekstensi PHP.
- Clone repo ini.
- Buka folder di IDE Anda.
- Lakukan perubahan pada `calendar.php` atau modul lainnya.
- Perubahan akan terlihat langsung saat di-refresh (tidak perlu proses build untuk PHP).

**Edit file langsung di GitHub**

- Navigasi ke file yang diinginkan.
- Klik tombol "Edit" (ikon pensil) di kanan atas tampilan file.
- Lakukan perubahan dan commit perubahan tersebut.

## Bagaimana cara mendeploy proyek ini?

1.  **Upload File**: Upload isi folder root ke `public_html` hosting Anda melalui FTP atau File Manager.
2.  **Database**: Ekspor database lokal Anda (`.sql`) dan import ke Database hosting live Anda.
3.  **Konfigurasi**: Buka `config/database.php` (atau file koneksi) dan perbarui kredensialnya:
    ```php
    $host = 'localhost';
    $user = 'user_hosting_anda';
    $pass = 'pass_hosting_anda';
    $db   = 'nama_db_hosting_anda';
    ```
4.  **Selesai**: Aplikasi Anda sekarang sudah live!

## Bisakah saya menghubungkan domain kustom?

Ya! Jika menggunakan cPanel atau VPS, cukup arahkan **A Record** domain Anda ke IP server Anda atau atur Nameservers ke penyedia hosting Anda.

---
*Dikelola oleh [Fadlan27](https://github.com/fadlan27)*