# BAB 1
# PENDAHULUAN

## 1.1 Latar Belakang Masalah

Arsip merupakan salah satu aset penting bagi organisasi, instansi pemerintah, maupun lembaga pendidikan. Arsip berfungsi sebagai bukti otentik kegiatan, alat pengambilan keputusan, dan sumber informasi historis. Berdasarkan Undang-Undang Nomor 27 Tahun 2014 tentang Kearsipan, setiap lembaga negara dan pemerintah wajib melakukan pengelolaan arsip secara sistematis dan terintegrasi.

Namun dalam praktiknya, banyak instansi masih menyimpan arsip dalam bentuk fisik — kertas, map, dan lemari arsip. Pencarian dokumen tertentu membutuhkan waktu lama, risiko kerusakan akibat kelembapan atau usia tinggi, serta sulitnya melakukan distribusi informasi lintas divisi. Ketika arsip harus dipindahkan atau dicari, proses manual ini menjadi hambatan serius dalam efisiensi kerja.

Perkembangan teknologi Optical Character Recognition (OCR) dan Artificial Intelligence (AI) menawarkan solusi untuk masalah ini. OCR memungkinkan konversi gambar dokumen menjadi teks yang dapat diproses secara digital. Sementara itu, Vision Language Model (VLM) seperti Google Gemini Vision API mampu melakukan analisis semantik — tidak hanya membaca teks, tetapi juga memahami konteks, mengenali jenis dokumen, dan mengekstrak metadata secara otomatis.

Penelitian ini mengembangkan sistem digitalisasi arsip yang mengintegrasikan kedua teknologi tersebut dalam satu pipeline. Tesseract OCR digunakan untuk ekstraksi teks mentah, sedangkan Gemini Vision API digunakan untuk analisis metadata terstruktur (judul, tahun, nomor dokumen, jenis arsip, instansi, dll). Sistem ini dirancang dengan mekanisme fallback bertingkat 3-layer untuk memastikan proses tetap berjalan meskipun salah satu engine mengalami kegagalan.

Pendekatan dual-engine dengan fallback ini belum banyak diimplementasikan pada sistem manajemen arsip berbasis web, sehingga penelitian ini diharapkan memberikan kontribusi dalam bidang pengelolaan arsip digital.

## 1.2 Rumusan Masalah

Berdasarkan latar belakang yang telah diuraikan, rumusan masalah dalam penelitian ini adalah:

1. Bagaimana merancang dan mengimplementasikan pipeline OCR dan AI yang terintegrasi untuk digitalisasi arsip dokumen?
2. Bagaimana mekanisme fallback bertingkat 3-layer bekerja dalam menangani kegagalan engine pada proses ekstraksi metadata?
3. Seberapa efektif penggunaan Tesseract OCR dan Google Gemini Vision API dalam mengekstrak metadata arsip secara otomatis?
4. Bagaimana performa sistem dalam hal waktu proses dan tingkat keberhasilan ekstraksi metadata?

## 1.3 Tujuan Penelitian

Penelitian ini bertujuan untuk:

1. Merancang dan mengimplementasikan sistem digitalisasi arsip berbasis web yang mengintegrasikan Tesseract OCR dan Google Gemini Vision API.
2. Mengembangkan mekanisme fallback bertingkat 3-layer (Gemini API → Regex OCR → Nama File) untuk memastikan kelangsungan proses ekstraksi metadata.
3. Mengevaluasi efektivitas pipeline dual-engine dalam mengekstrak metadata arsip (judul, tahun, nomor dokumen, jenis arsip, instansi) dari gambar dokumen.
4. Mengukur performa sistem berdasarkan waktu proses dan tingkat keberhasilan ekstraksi.

## 1.4 Manfaat Penelitian

### 1.4.1 Manfaat Akademis

- Menambah referensi penelitian di bidang integrasi OCR dan Vision Language Model untuk pengelolaan arsip digital.
- Menjadi dasar pengembangan penelitian lanjutan terkait ekstraksi metadata dokumen otomatis.

### 1.4.2 Manfaat Praktis

- **Instansi pemerintah/organisasi**: Mempercepat proses digitalisasi arsip fisik menjadi arsip digital yang terstruktur dan mudah dicari.
- **Divisi kearsipan**: Mengurangi waktu pencarian dokumen dari jam menjadi detik.
- **Pengembang perangkat lunak**: Menjadi referensi implementasi integrasi OCR + AI API pada aplikasi web.

## 1.5 Ruang Lingkup

Penelitian ini difokuskan pada:

1. Jenis dokumen arsip berformat gambar (JPG dan PNG).
2. Ekstraksi metadata: judul, tahun, nomor dokumen, jenis arsip, jenis kegiatan, nama kegiatan, instansi, dan ringkasan.
3. Teknologi yang digunakan: Tesseract OCR, Google Gemini Vision API, PHP 8.x, MySQL, Bootstrap 5.
4. Platform: aplikasi web yang dijalankan pada server lokal (XAMPP).

## 1.6 Sistematika Penulisan

Penelitian ini disusun dalam 5 bab sebagai berikut:

**BAB I — Pendahuluan**
Berisi latar belakang masalah, rumusan masalah, tujuan penelitian, manfaat penelitian, ruang lingkup, dan sistematika penulisan.

**BAB II — Tinjauan Pustaka**
Berisi kajian teori meliputi: konsep kearsipan digital, teknologi OCR (Tesseract), konsep Vision Language Model (Gemini API), integrasi API pada aplikasi web, serta penelitian terdahulu yang relevan.

**BAB III — Metodologi Penelitian**
Berisi metode penelitian yang digunakan, arsitektur sistem, diagram alur pipeline OCR + Gemini AI, desain database, dan mekanisme fallback bertingkat.

**BAB IV — Hasil dan Pembahasan**
Berisi implementasi sistem: halaman upload, pipeline OCR + AI, mekanisme merge & fallback, manajemen arsip, ekspor dokumen, serta hasil pengujian performa.

**BAB V — Penutup**
Berisi kesimpulan, keterbatasan penelitian, dan saran pengembangan lanjutan.
