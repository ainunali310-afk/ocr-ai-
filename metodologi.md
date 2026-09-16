# Metodologi Pipeline OCR + Gemini AI

## Arsitektur Umum

Pipeline menggunakan pendekatan **dual-engine dengan fallback** — Tesseract OCR sebagai pembaca teks mentah, Gemini Vision API sebagai analisis semantik. Keduanya berjalan paralel pada dokumen yang sama, hasilnya digabungkan melalui mekanisme fallback bertingkat.

## Diagram Alur Pipeline

```mermaid
flowchart TD
    A[Upload Gambar JPG/PNG] --> B[STEP 1: Read Source]
    B --> C{Format File?}
    C -->|Gambar jpg/png| D[STEP 2: Tesseract OCR]
    C -->|Gambar jpg/png| E[STEP 3: Gemini Vision API]
    C -->|Non-gambar| F[Langsung ke STEP 4]
    D --> G[Raw Text]
    E --> H[Metadata Terstruktur 10 Field]
    G --> I[STEP 4: Merge & Fallback]
    H --> I
    F --> I
    I --> J{Ada field kosong?}
    J -->|Ya| K[extractMetadataFromText - Regex Fallback]
    K --> L{Judul masih kosong?}
    L -->|Ya| M[Pakai nama file sebagai judul]
    L -->|Tidak| N[Simpan ke Database]
    M --> N
    J -->|Tidak| N
```

## Diagram Fallback 3 Layer

```mermaid
flowchart LR
    A[Field Kosong?] --> B{Layer 1: Gemini API}
    B -->|Ada data| C[Gunakan hasil Gemini]
    B -->|Kosong/Gagal| D{Layer 2: Regex OCR}
    D -->|Ada data| E[Gunakan hasil regex]
    D -->|Kosong| F{Layer 3: Nama File}
    F --> G[Gunakan nama file]
    C --> H[Final Metadata]
    E --> H
    G --> H
```

## STEP 2: Tesseract OCR

**File**: `helpers/ocr.php:13-40`

**Tujuan**: Ekstrak teks mentah dari gambar dokumen.

```mermaid
flowchart TD
    A[Mulai OCR] --> B[Cari Tesseract di 3 kandidat path]
    B --> C{Path 1: Program Files?}
    C -->|Ada| D[Jalankan tesseract image stdout]
    C -->|Tidak| E{Path 2: Program Files x86?}
    E -->|Ada| D
    E -->|Tidak| F{Path 3: System PATH?}
    F -->|Ada| D
    F -->|Tidak| G[Kembalikan string kosong]
    D --> H{Output valid?}
    H -->|Ya| I[Kembalikan teks hasil OCR]
    H -->|Tidak error| G
```

**Fallback regex** (`helpers/ocr.php:63-157`):

| Metadata | Metode Ekstraksi | Regex/Pattern |
|----------|-----------------|---------------|
| Tahun | Regex | `\b(19\d{2}\|20\d{2}\|2100)\b` |
| Jenis arsip | Keyword matching | laporan, proposal, surat, sertifikat, undangan, dll |
| Nomor dokumen | Regex | `Nomor\s*[:\-]?\s*([A-Za-z0-9\/.\-]+)` |
| Nama kegiatan | Regex | `(?:Nama Kegiatan\|Kegiatan\|Acara)\s*[:\-]?\s*(.+)` |
| Instansi | Regex | `(?:Instansi\|Dinas\|Kementerian\|...)\s*[:\-]?\s*(.+)` |

## STEP 3: Gemini Vision API

**File**: `helpers/gemini.php:14-117`

**Tujuan**: Analisis semantik — AI membaca isi dokumen dan mengekstrak metadata terstruktur.

```mermaid
flowchart TD
    A[Mulai Gemini API] --> B[Baca file gambar]
    B --> C[Encode base64]
    C --> D[Deteksi MIME type]
    D --> E[Bangun payload JSON]
    E --> F[Coba model gemini-2.0-flash]
    F --> G{HTTP 200?}
    G -->|Ya| H[Parse response JSON]
    G -->|Tidak| I[Coba model gemini-2.5-flash]
    I --> J{HTTP 200?}
    J -->|Ya| H
    J -->|Tidak| K[Coba model gemini-flash-latest]
    K --> L{HTTP 200?}
    L -->|Ya| H
    L -->|Tidak| M[Kembalikan null]
    H --> N[Strip markdown fences]
    N --> O[Regex extract JSON object]
    O --> P{json_decode valid?}
    P -->|Ya| Q[Kembalikan 10 field metadata]
    P -->|Tidak| M
```

**Payload yang dikirim ke API**:

```json
{
  "contents": [{
    "parts": [
      {
        "text": "Baca gambar dokumen arsip ini dan ekstrak semua metadata penting..."
      },
      {
        "inline_data": {
          "mime_type": "image/jpeg",
          "data": "base64..."
        }
      }
    ]
  }]
}
```

**10 Field output Gemini**:

| Field | Deskripsi | Tipe |
|-------|-----------|------|
| `raw_text` | Teks mentah seluruh dokumen | string |
| `judul` | Judul dokumen | string |
| `tahun` | Tahun pembuatan | int |
| `nomor_dokumen` | Nomor surat/dokumen | string |
| `jenis_arsip` | Surat, Laporan, Proposal, dll | string |
| `jenis_kegiatan` | Kegiatan, Seminar, Pelatihan, dll | string |
| `nama_kegiatan` | Nama kegiatan spesifik | string |
| `instansi` | Instansi/organisasi | string |
| `ringkasan` | Ringkasan isi dokumen | string |
| `data_terstruktur` | JSON objek data tambahan | object |

## STEP 4: Merge & Fallback

**File**: `process.php:41-83`

```mermaid
flowchart TD
    A[Menerima hasil Gemini + OCR] --> B{Gemini berhasil?}
    B -->|Ya| C[Pakai hasil Gemini]
    B -->|Tidak null| D{Judul/Tahun/Kegiatan kosong?}
    D -->|Ya| E[Jalankan extractMetadataFromText]
    E --> F[Merge: field kosong diisi dari regex]
    D -->|Tidak| G[Simpan ke database]
    C --> D
    F --> H{Judul masih kosong?}
    H -->|Ya| I[Pakai nama file sebagai judul]
    H -->|Tidak| G
    I --> G
```

**Logika merge**:

```php
$judul    = $judul    ?: $metadata['judul'];
$tahun    = $tahun    ?: $metadata['tahun'];
$jenisKegiatan = $jenisKegiatan ?: $metadata['jenis_kegiatan'];
$jenisArsip    = $jenisArsip    ?: $metadata['jenis_arsip'];
$nomorDokumen  = $nomorDokumen  ?: $metadata['nomor_dokumen'];
$namaKegiatan  = $namaKegiatan  ?: $metadata['nama_kegiatan'];
$instansi      = $instansi      ?: $metadata['instansi'];
```

## Kelebihan Pendekatan

| Aspek | Detail |
|-------|--------|
| Redundansi | 3 layer fallback jarang gagal total |
| Paralelisme | OCR dan AI berjalan bersamaan |
| Model fleksibel | Model Gemini bisa diubah via constant `GEMINI_MODELS` |
| Graceful degradation | Non-gambar (PDF/DOC) tetap diproses tanpa OCR |
| Konsistensi output | Selalu 10 field JSON regardless of source |

## Keterbatasan

| Aspek | Detail |
|-------|--------|
| Tanpa confidence score | Hasil OCR/AI diterima tanpa skor keyakinan |
| Tesseract tanpa `--lang ind` | Default bahasa Inggris |
| Prompt statis | Tidak ada prompt per jenis dokumen |
| Tanpa caching | Dokumen sama diproses ulang jika di-reupload |
