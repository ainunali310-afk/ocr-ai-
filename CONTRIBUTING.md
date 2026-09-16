# Contributing

Terima kasih ingin berkontribusi! Berikut panduannya.

## Cara Mulai

1. **Fork** repo ini
2. **Clone** fork kamu:
   ```bash
   git clone https://github.com/YOUR_USERNAME/arsip-digital.git
   ```
3. **Buat branch** untuk fitur/fix:
   ```bash
   git checkout -b fitur/nama-fitur
   ```
4. Buat file `.env` dari `.env.example` dan isi API key lokal
5. Jalankan di XAMPP, pastikan一切 jalan

## Branch Naming

| Tipe | Format | Contoh |
|------|--------|--------|
| Fitur baru | `fitur/nama-fitur` | `fitur/export-pdf` |
| Bug fix | `fix/nama-fix` | `fix/upload-error` |
| Dokumentasi | `docs/nama-file` | `docs/readme-update` |

## Code Style

- **PHP:** Ikuti style yang sudah ada (snake_case untuk function, PascalCase untuk class)
- **Indentasi:** 4 spasi
- **String:** Prefer double quotes `" "` untuk interpolasi
- **Naming:** Gunakan bahasa Indonesia untuk variabel UI, English untuk kode internal
- **Komentar:** Hanya jika diperlukan — jangan komentari kode yang sudah jelas

## Yang Perlu Diperhatikan

- **Jangan commit `.env`** — berisi API key dan kredensial
- **Jangan commit `uploads/`** — berisi file pengguna
- **Jangan commit `logs/`** dan `cache/`**
- Selalu test di environment lokal sebelum submit PR
- Pastikan tidak ada hardcoded credentials

## Pull Request

1. Push branch ke fork kamu
2. Buka PR ke branch `main`
3. Isi deskripsi PR:
   - Apa yang diubah
   - Kenapa diubah
   - Cara test
4. Tunggu review

## Bug Report

Buka issue dengan format:

```
**Judul:** [Singkat]
**Deskripsi:** [Langkah untuk mereproduksi]
**Expected:** [Yang diharapkan]
**Actual:** [Yang terjadi]
**Screenshot:** [Kalau ada]
```

## Feature Request

Buka issue dengan label `enhancement` dan jelaskan:
- Masalah yang ingin diselesaikan
- Solusi yang diusulkan
- Alternatif yang dipertimbangkan

## Pertanyaan?

Buka issue dengan label `question` atau diskusi di Discussion (kalau tersedia).
