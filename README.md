# Arsip Digital

Sistem manajemen arsip digital dengan fitur **OCR**, **AI Ekstraksi Metadata**, dan **template cetak**. Unggah arsip fisik/digital, ekstrak data otomatis via Gemini AI, dan simpan sebagai arsip terstruktur.

## Fitur

- **Upload & OCR** — Unggah gambar arsip (JPG/PNG), OCR otomatis ekstrak teks
- **AI Ekstraksi** — Gemini AI ambil metadata (judul, nomor, tahun, instansi) dari hasil OCR
- **Template Cetak** — Generate output sesuai template arsip
- **Kategori & Pencarian** — Kelompokkan arsip, cari by metadata/teks
- **Multi-user** — Login, registrasi, role admin/user
- **Riwayat** — Lacak aktivitas upload dan proses
- **Export** — Ekspor data arsip ke format lain
- **Responsive** — Mobile-friendly, PWA-capable

## Persyaratan

| Software | Versi |
|----------|-------|
| PHP | >= 7.4 (direkomendasikan 8.x) |
| MySQL/MariaDB | >= 5.7 |
| XAMPP/Laragon/WAMP | Latest |
| Gemini API Key | [Dapatkan di Google AI Studio](https://aistudio.google.com/apikey) |

## Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/USERNAME/arsip-digital.git
cd arsip-digital
```

### 2. Konfigurasi Environment

Copy `.env.example` ke `.env` lalu isi nilai yang sesuai:

```bash
cp .env.example .env
```

```env
GEMINI_API_KEY=your_api_key_here
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=arsip_digital
APP_DEBUG=true
UPLOAD_DIR=./uploads
MAX_FILE_SIZE=10485760
```

### 3. Jalankan Server

Buka XAMPP, nyalakan **Apache** + **MySQL**. Buka browser:

```
http://localhost/arsip-digital
```

Database dan tabel otomatis dibuat saat pertama kali dijalankan.

### 4. Login Default

| Field | Value |
|-------|-------|
| Username | `admin` |
| Password | `admin123` |

> **Ubah password default segera** setelah login pertama.

## Struktur Projek

```
├── assets/              # CSS, logo, aset statis
├── cache/               # Cache sementara (jangan commit)
├── config/              # Konfigurasi DB & settings
│   ├── database.php     # Koneksi MySQL + autoload env
│   └── settings.php     # Konstanta app, schema DB, inisialisasi
├── helpers/             # Fungsi pembantu
│   ├── csrf.php         # CSRF token protection
│   ├── file.php         # Utilitas file
│   ├── gemini.php       # Integrasi Gemini AI API
│   ├── ocr.php          # Proses OCR
│   └── sanitize.php     # Input sanitization
├── logs/                # Error logs (jangan commit)
├── uploads/             # File arsip yang diunggah (jangan commit)
├── auth.php             # Autentikasi & otorisasi
├── config.php           # Bootstrap & loader
├── delete.php           # Hapus arsip
├── detail.php           # Detail arsip
├── export.php           # Ekspor data
├── history.php          # Riwayat aktivitas
├── index.php            # Halaman utama (dashboard)
├── login.php            # Halaman login
├── logout.php           # Logout
├── manage.php           # Manajemen arsip
├── process.php          # Proses upload & OCR
├── register.php         # Registrasi user
├── upload.php           # Form upload
└── .env                 # Environment config (jangan commit)
```

## Tech Stack

- **Backend:** PHP (procedural + OOP)
- **Database:** MySQL / MariaDB
- **Frontend:** Bootstrap 5, Bootstrap Icons
- **AI:** Google Gemini API
- **OCR:** Tesseract (via exec/shell) atau fallback PHP

## Kontribusi

Lihat [CONTRIBUTING.md](CONTRIBUTING.md) untuk panduan kontribusi.

## Lisensi

MIT License — Lihat [LICENSE](LICENSE).

## Security Note

- `.env` mengandung API key dan kredensial DB — **jangan pernah commit**
- `uploads/` berisi file arsip pengguna — **jangan commit**
- `cache/` dan `logs/` bersifat sementara — **jangan commit**
- Selalu gunakan HTTPS di production
