# BAB I
# PENDAHULUAN

## 1.1 Latar Belakang

Transformasi digital telah mengubah cara instansi pemerintah, organisasi, dan lembaga pendidikan menjalankan kegiatan administrasinya. Di lingkungan pemerintahan, arah perubahan tersebut ditegaskan melalui Peraturan Presiden Nomor 95 Tahun 2018 tentang Sistem Pemerintahan Berbasis Elektronik (SPBE), yang mendorong penyelenggaraan pemerintahan secara terpadu dengan memanfaatkan teknologi informasi dan komunikasi. Seiring meluasnya layanan elektronik serta kegiatan alih media arsip kertas ke bentuk digital, volume dokumen digital yang dihasilkan setiap instansi terus bertambah [PERLU SITASI: pertumbuhan volume dokumen/arsip digital di instansi]. Dokumen tersebut bukan sekadar berkas pendukung, melainkan arsip yang berfungsi sebagai bukti otentik pelaksanaan kegiatan, bahan pengambilan keputusan, dan sumber informasi historis. Undang-Undang Nomor 43 Tahun 2009 tentang Kearsipan mewajibkan penyelenggaraan kearsipan dilakukan secara sistematis agar arsip yang autentik dan terpercaya tersedia serta dapat diakses ketika dibutuhkan. Dengan demikian, pertumbuhan arsip digital menuntut pengelolaan yang tidak hanya menyimpan, tetapi juga menjamin arsip dapat ditemukan kembali secara cepat dan tepat.

Digitalisasi tidak dengan sendirinya membuat arsip terkelola dengan baik. Pada awalnya, keterbatasan arsip fisik yang memerlukan ruang simpan, rentan rusak, dan lama dicari mendorong instansi melakukan pemindaian dokumen. Namun, hasil pemindaian kerap hanya dipindahkan ke folder komputer atau layanan penyimpanan daring tanpa struktur klasifikasi yang jelas. Penamaan berkas tidak seragam antarpetugas, misalnya "scan_001.jpg", "undangan fix.png", atau nama bawaan perangkat pemindai, sehingga isi berkas tidak dapat dikenali dari namanya. Berkas yang sama juga sering tersimpan berulang kali di lokasi berbeda sehingga menimbulkan duplikasi dan keraguan mengenai versi yang sah. Akibatnya, petugas tetap harus membuka berkas satu per satu untuk menemukan dokumen tertentu, dan permintaan arsip dari divisi lain tidak dapat dilayani dengan segera. Kondisi ini menunjukkan bahwa persoalan arsip digital tidak hanya terletak pada media penyimpanannya, tetapi juga pada bentuk data yang dihasilkan.

Sebagian besar arsip hasil alih media berbentuk citra digital seperti JPG dan PNG, yang tergolong data tidak terstruktur (*unstructured data*), yaitu data yang tidak memiliki skema baku sehingga tidak dapat langsung diolah oleh sistem basis data [PERLU SITASI: definisi data tidak terstruktur]. Bagi komputer, citra hasil pindai hanyalah susunan piksel; teks yang terbaca jelas oleh manusia tidak dapat dibaca, diindeks, maupun dicari oleh sistem. Informasi penting seperti nomor surat, nama instansi pengirim, tanggal, dan perihal akhirnya terkunci di dalam gambar. Pencarian hanya dapat mengandalkan nama berkas atau keterangan yang diketik ulang oleh petugas. Selama isi dokumen belum diubah menjadi data yang dapat dibaca mesin, arsip digital pada praktiknya tidak lebih mudah ditemukan daripada arsip kertas. Untuk menjembatani hal tersebut, instansi umumnya bergantung pada metadata yang diisi secara manual.

Metadata, yaitu data yang mendeskripsikan arsip seperti judul, tahun, nomor dokumen, jenis arsip, dan instansi pencipta, merupakan kunci dalam proses temu kembali arsip [PERLU SITASI: peran metadata dalam temu kembali arsip]. Melalui metadata, arsip dapat dikelompokkan, disaring, dan ditemukan tanpa harus membuka isinya satu per satu. Namun, pengisian metadata secara manual membutuhkan waktu yang lama, terutama ketika jumlah arsip yang dialihmediakan besar. Proses ini juga rawan salah ketik, tidak konsisten antarpetugas dalam menuliskan nama instansi atau jenis arsip, dan menambah beban kerja di luar tugas pokok petugas. Kualitas metadata yang buruk berdampak langsung pada kualitas pencarian, karena arsip dengan tahun yang keliru atau nama instansi yang ditulis berbeda tidak akan muncul ketika dicari. Oleh karena itu, diperlukan mekanisme yang membantu petugas mengekstrak metadata secara otomatis dan konsisten dari isi dokumen. Meskipun demikian, metadata yang baik pun belum menjamin arsip mudah ditemukan apabila cara pencariannya masih terbatas.

Pendekatan pencarian konvensional berbasis pencocokan kata kunci (*keyword search*) memiliki keterbatasan mendasar karena hanya menemukan dokumen yang memuat istilah yang persis sama dengan kueri pengguna [PERLU SITASI: keterbatasan pencocokan kata kunci/lexical matching]. Pencarian semacam ini tidak memahami sinonim, misalnya "undangan" dan "surat permohonan kehadiran", maupun singkatan seperti "Disdik" untuk "Dinas Pendidikan". Pencarian kata kunci juga tidak mampu menafsirkan ungkapan waktu relatif seperti "tahun lalu" atau "bulan kemarin" menjadi rentang tanggal yang konkret. Padahal, kebutuhan nyata pengguna umumnya berbentuk kalimat sehari-hari, misalnya "cari surat undangan seminar tahun lalu dari dinas pendidikan". Jika kueri tersebut dicocokkan kata demi kata, dokumen yang relevan dapat terlewat, sedangkan dokumen yang tidak relevan justru ikut muncul. Akibatnya, pengguna dari divisi lain harus menebak istilah yang tepat atau kembali meminta bantuan petugas arsip. Keterbatasan pada sisi hulu (ekstraksi metadata) dan sisi hilir (pencarian) inilah yang mendorong pemanfaatan teknologi pemrosesan dokumen dan pencarian yang lebih cerdas.

*Intelligent Document Processing* (IDP) merupakan pendekatan pengolahan dokumen yang memadukan *Optical Character Recognition* (OCR), pemahaman bahasa alami, dan ekstraksi informasi terstruktur dalam satu alur kerja. Kajian *Document AI* menunjukkan bahwa model yang memahami teks sekaligus tata letak dokumen mampu mengekstrak informasi dari dokumen pindaian secara otomatis (Cui dkk., 2021). Pada tingkat dasar, Tesseract OCR dapat mengubah citra dokumen menjadi teks mentah yang dapat diproses secara digital (Smith, 2007). Lebih jauh, *Vision Language Model* (VLM) seperti Google Gemini mampu membaca citra secara langsung, memahami tata letak dan konteks dokumen, serta mengenali jenis arsip beserta atribut pentingnya [PERLU SITASI: Gemini/VLM untuk pemahaman dokumen]. Dengan IDP, informasi yang sebelumnya terkunci di dalam citra dapat diubah menjadi metadata terstruktur sekaligus mengurangi beban pengisian manual. Namun, ketergantungan pada satu mesin pemroses menimbulkan risiko proses terhenti ketika layanan AI tidak tersedia, kuota habis, atau citra berkualitas rendah. Oleh karena itu, alur IDP perlu dilengkapi mekanisme *fallback* bertingkat, yaitu rangkaian cara cadangan yang dijalankan berurutan agar metadata tetap dihasilkan meskipun salah satu mesin gagal.

Di sisi pencarian, pencarian semantik (*semantic search*) menawarkan cara menemukan dokumen berdasarkan makna kueri, bukan sekadar kecocokan deretan karakter. Landasan konsep ini adalah kemiripan semantik, yaitu gagasan bahwa dua kalimat dengan susunan kata berbeda dapat memiliki arti yang sama dan dapat diukur kedekatannya, misalnya melalui representasi vektor (*embedding*) kalimat (Reimers & Gurevych, 2019). Kemampuan model bahasa untuk menangkap makna tersebut berakar pada arsitektur *Transformer* (Vaswani dkk., 2017). Penelitian ini tidak membangun indeks vektor, tetapi memanfaatkan *Generative AI* untuk menerjemahkan kueri bahasa alami menjadi kriteria pencarian terstruktur atas metadata dan teks arsip, seperti tahun, jenis arsip, instansi, dan kata kunci isi. Dengan cara ini, kueri "tahun lalu dari dinas pendidikan" dapat diterjemahkan menjadi tahun tertentu dan instansi yang dimaksud. Apabila layanan AI tidak tersedia atau penafsirannya tidak menemukan hasil, sistem kembali menggunakan pencarian kata kunci sebagai cadangan. Pendekatan ini menjawab keterbatasan pencarian kata kunci tanpa menghilangkan keandalannya.

Perkembangan *Large Language Model* (LLM) dan *Generative AI* dalam beberapa tahun terakhir membuka peluang pemanfaatan model bahasa untuk berbagai tugas pengolahan informasi. Namun, model generatif berisiko mengalami halusinasi, yaitu menghasilkan keluaran yang tampak meyakinkan tetapi tidak sesuai dengan fakta, terutama ketika model menjawab hanya dari pengetahuan internalnya [PERLU SITASI: halusinasi pada LLM]. *Retrieval-Augmented Generation* (RAG) diusulkan untuk mengurangi risiko tersebut dengan menambatkan keluaran model pada dokumen yang diambil dari sumber data nyata (Lewis dkk., 2020). Penelitian ini mengadopsi prinsip penambatan (*grounding*) tersebut secara ringan: Gemini hanya digunakan untuk memahami kueri dan mengekstrak metadata, sedangkan hasil pencarian selalu diambil dari basis data arsip sehingga sistem tidak pernah mengarang dokumen, dan verifikasi akhir metadata tetap berada di tangan petugas arsip. Meskipun IDP dan pencarian semantik telah banyak diteliti secara terpisah, integrasi keduanya dalam satu platform pengarsipan digital berbasis web yang dilengkapi mekanisme *fallback* bertingkat masih jarang diimplementasikan [PERLU SITASI: penelitian terdahulu IDP/OCR arsip dan pencarian semantik]. Oleh karena itu, penelitian ini mengembangkan platform pengarsipan digital yang memadukan *Intelligent Document Processing* berbasis Tesseract OCR dan Google Gemini Vision API dengan *fallback* bertingkat untuk ekstraksi metadata, serta pencarian semantik berbasis *Generative AI* untuk temu kembali arsip, sehingga arsip digital dapat dikelola secara lebih cepat, konsisten, dan mudah ditemukan kembali.

## 1.2 Rumusan Masalah

Berdasarkan latar belakang yang telah diuraikan, rumusan masalah dalam penelitian ini adalah sebagai berikut.

1. Bagaimana merancang alur *Intelligent Document Processing* yang mengintegrasikan OCR dan *Generative AI* untuk mengekstrak metadata arsip secara otomatis dari citra dokumen?
2. Bagaimana merancang mekanisme *fallback* bertingkat agar proses ekstraksi tetap menghasilkan metadata meskipun salah satu mesin pemroses gagal?
3. Bagaimana menerapkan pencarian semantik berbasis *Generative AI* agar dokumen yang relevan tetap ditemukan ketika pengguna menuliskan kueri dalam bahasa alami?
4. Sejauh mana platform yang dibangun mampu meningkatkan akurasi ekstraksi metadata dan efisiensi pencarian arsip dibandingkan pengelolaan arsip secara manual?

## 1.3 Batasan Masalah

Agar penelitian lebih terarah, ruang lingkup penelitian dibatasi sebagai berikut.

1. Arsip yang diproses adalah dokumen administratif berformat citra (JPG dan PNG). Berkas non-citra tetap dapat diunggah, tetapi diproses tanpa tahap OCR.
2. Metadata yang diekstraksi dibatasi pada sepuluh atribut, yaitu teks mentah, judul, tahun, nomor dokumen, jenis arsip, jenis kegiatan, nama kegiatan, instansi, ringkasan, dan data terstruktur tambahan.
3. Mekanisme *fallback* terdiri atas tiga lapis berurutan: Google Gemini Vision API, ekstraksi pola (*regular expression*) atas teks hasil OCR, dan nama berkas.
4. Pencarian semantik dibatasi pada penerjemahan kueri bahasa alami menjadi kriteria pencarian terstruktur atas metadata dan teks hasil ekstraksi menggunakan *Generative AI*, dengan pencarian kata kunci sebagai cadangan. Penelitian ini tidak membangun indeks vektor dan tidak melatih model sendiri.
5. Teknologi yang digunakan meliputi Tesseract OCR, Google Gemini Vision API, PHP 8.x, MySQL, dan Bootstrap 5, dengan platform berbasis web yang dijalankan pada server lokal (XAMPP).
6. Pengguna sistem terdiri atas tiga peran, yaitu admin, anak magang sebagai petugas input arsip, dan pengguna divisi lain sebagai pencari arsip.
7. Sistem berfungsi sebagai alat bantu pengelolaan arsip, sehingga verifikasi akhir atas kebenaran metadata tetap berada pada petugas arsip.

## 1.4 Tujuan Penelitian

Tujuan penelitian ini adalah sebagai berikut.

1. Merancang dan membangun alur *Intelligent Document Processing* pada platform pengarsipan digital berbasis web yang mengintegrasikan Tesseract OCR dan Google Gemini Vision API untuk mengekstrak metadata arsip secara otomatis dari citra dokumen.
2. Mengembangkan mekanisme *fallback* bertingkat tiga lapis (Gemini API → ekstraksi *regex* atas teks OCR → nama berkas) untuk menjamin proses ekstraksi tetap menghasilkan metadata ketika salah satu mesin pemroses gagal.
3. Menerapkan modul pencarian semantik berbasis *Generative AI* yang menerjemahkan kueri bahasa alami menjadi kriteria pencarian terstruktur, dengan pencarian kata kunci sebagai cadangan.
4. Mengukur akurasi ekstraksi metadata dan efisiensi pencarian arsip pada platform yang dibangun dibandingkan dengan pengelolaan arsip secara manual.

## 1.5 Manfaat Penelitian

Penelitian ini diharapkan memberikan manfaat sebagai berikut.

1. **Bagi instansi dan lembaga pengelola arsip**, mempercepat alih media arsip menjadi arsip digital yang terstruktur dan mudah ditemukan kembali, sejalan dengan kewajiban pengelolaan arsip dalam Undang-Undang Nomor 43 Tahun 2009 tentang Kearsipan.
2. **Bagi petugas kearsipan**, mengurangi beban pengisian metadata secara manual serta menekan risiko salah ketik dan ketidakkonsistenan penamaan antarpetugas.
3. **Bagi pengguna divisi lain**, memudahkan pencarian arsip menggunakan kalimat sehari-hari tanpa harus mengetahui istilah persis yang tertulis di dalam dokumen.
4. **Bagi pengembang perangkat lunak**, menjadi rujukan implementasi integrasi OCR dan *Generative AI* pada aplikasi web, khususnya pola pemrosesan ganda dengan *fallback* bertingkat.
5. **Bagi peneliti dan bidang keilmuan**, menjadi referensi penelitian lanjutan di bidang *Intelligent Document Processing*, ekstraksi metadata otomatis, dan pencarian semantik pada sistem kearsipan digital.

## 1.6 Metodologi Penelitian

Penelitian ini menggunakan metode pengembangan sistem model *prototyping* dengan tahapan sebagai berikut.

1. **Studi literatur**, yaitu mengkaji teori dan penelitian terdahulu mengenai kearsipan dan metadata, OCR, *Vision Language Model*, *Intelligent Document Processing*, pencarian semantik, dan *Retrieval-Augmented Generation*.
2. **Pengumpulan data dan analisis kebutuhan**, yaitu observasi proses pengelolaan arsip dan wawancara dengan petugas arsip untuk menentukan atribut metadata serta kebutuhan tiap peran pengguna, disertai pengumpulan sampel citra arsip.
3. **Perancangan sistem**, meliputi arsitektur alur pemrosesan dokumen, desain basis data, alur *fallback* bertingkat, rancangan modul pencarian semantik, diagram *use case* untuk tiga peran pengguna, dan desain antarmuka.
4. **Pembangunan prototipe**, yaitu implementasi platform menggunakan PHP, MySQL, dan Bootstrap 5, dengan pemrosesan citra secara paralel oleh Tesseract OCR dan Google Gemini Vision API yang hasilnya digabungkan melalui mekanisme *fallback*.
5. **Evaluasi prototipe**, yaitu pengujian dan umpan balik pengguna sebagai dasar perbaikan prototipe sebelum sistem difinalkan.
6. **Pengujian**, meliputi pengujian fungsional (*black box*) per peran pengguna, akurasi ekstraksi metadata dibandingkan anotasi manual, skenario kegagalan untuk menguji *fallback*, relevansi hasil pencarian semantik dibandingkan pencarian kata kunci, serta perbandingan waktu input dan temu kembali arsip antara cara manual dan sistem.

## 1.7 Sistematika Penulisan

Laporan skripsi ini disusun dalam lima bab dengan sistematika sebagai berikut.

**BAB I PENDAHULUAN**
Berisi latar belakang, rumusan masalah, batasan masalah, tujuan penelitian, manfaat penelitian, metodologi penelitian, dan sistematika penulisan.

**BAB II TINJAUAN PUSTAKA**
Berisi penelitian terdahulu yang relevan serta landasan teori meliputi kearsipan dan metadata, OCR dan Tesseract, *Vision Language Model* dan Google Gemini, *Intelligent Document Processing*, pencarian semantik, *Retrieval-Augmented Generation*, model *prototyping*, PHP dan MySQL, serta pengujian *black box*.

**BAB III METODOLOGI PENELITIAN**
Berisi objek dan lokasi penelitian, teknik pengumpulan data, tahapan *prototyping*, analisis kebutuhan, perancangan sistem (arsitektur, basis data, alur *fallback*, *use case*, antarmuka), serta rancangan pengujian dan metrik evaluasi.

**BAB IV HASIL DAN PEMBAHASAN**
Berisi implementasi platform, hasil pengujian fungsional, akurasi ekstraksi metadata, pengujian *fallback*, relevansi pencarian semantik, perbandingan dengan pengelolaan manual, serta pembahasan untuk setiap rumusan masalah.

**BAB V PENUTUP**
Berisi kesimpulan yang menjawab rumusan masalah serta saran untuk pengembangan penelitian selanjutnya.
