# Database Mahasantri & EduCal Pro

## Info Proyek

**URL**: https://github.com/fadlan27/Database-Mahasantri

## Bagaimana cara mengedit kode ini?

Ada beberapa cara untuk mengedit aplikasi Anda.

**Gunakan IDE pilihan Anda**

Jika Anda ingin bekerja secara lokal menggunakan IDE sendiri (seperti VS Code), Anda dapat meng-clone repo ini dan melakukan push perubahan.

Satu-satunya persyaratan adalah memiliki server PHP lokal (Laragon/XAMPP) & Git.

Ikuti langkah-langkah ini:

```sh
# Langkah 1: Clone repositori menggunakan URL Git proyek.
git clone https://github.com/fadlan27/Database-Mahasantri.git

# Langkah 2: Masuk ke direktori proyek.
cd "Database Mahasantri"

# Langkah 3: Persiapan Database.
# Buka phpMyAdmin, buat database 'mahasantri_db', lalu import file SQL yang tersedia.

# Langkah 4: Jalankan server pengembangan.
# Buka Laragon/XAMPP, klik 'Start All'.
# Buka browser dan akses: http://database-mahasantri.test
```

**Edit file langsung di GitHub**

- Navigasi ke file yang diinginkan.
- Klik tombol "Edit" (ikon pensil) di kanan atas tampilan file.
- Lakukan perubahan dan commit perubahan tersebut.

## Teknologi apa yang digunakan?

Proyek ini dibangun dengan:

- PHP 8.x (Native)
- MySQL (Database)
- Tailwind CSS (CDN)
- Vanilla JavaScript
- Lucide Icons

## Bagaimana cara deploy proyek ini?

Cukup upload semua file ke `public_html` di hosting Anda dan import database. Sesuaikan konfigurasi di `config/database.php`.

## Bisakah saya menghubungkan domain kustom?

Ya, Anda bisa!

Untuk menghubungkan domain, arahkan DNS (A Record) domain Anda ke IP server hosting Anda.