<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();
$currentUser = getCurrentUser();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = $conn->prepare('SELECT * FROM archives WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$doc) { header('Location: index.php'); exit; }

// If ajax request - do actual processing
if (isset($_GET['action']) && $_GET['action'] === 'run') {
    header('Content-Type: application/json');

    $sourcePath = __DIR__ . '/' . $doc['file_path'];
    $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

    // Step 1: Read source document (OCR for images)
    $ocrText = '';
    if (in_array($ext, ['jpg','jpeg','png'])) {
        $ocrText = runOcr($sourcePath);
    }

    // Step 2+3+4: Use Gemini AI to read source + extract metadata
    $templatePath = !empty($doc['template_path']) ? __DIR__ . '/' . $doc['template_path'] : null;
    $aiPrompt = trim($doc['ai_prompt'] ?? '');
    $outputFormat = $doc['output_format'] ?? 'pdf';
    $geminiApiKey = GEMINI_API_KEY;

    $aiData = null;
    if (in_array($ext, ['jpg','jpeg','png'])) {
        $aiData = extractDataWithGemini($sourcePath, $geminiApiKey, $aiPrompt);
    }

    if (!$aiData) {
        $aiData = [
            'raw_text' => $ocrText ?: 'Dokumen tidak dapat dibaca (format: .' . $ext . '). Upload gambar untuk hasil OCR terbaik.',
            'judul' => pathinfo($doc['file_name'], PATHINFO_FILENAME),
            'tahun' => null,
            'nomor_dokumen' => null,
            'jenis_arsip' => null,
            'jenis_kegiatan' => null,
            'nama_kegiatan' => null,
            'instansi' => null,
            'ringkasan' => null,
            'data_terstruktur' => null,
        ];
    }

    $rawText = $aiData['raw_text'] ?? $ocrText;
    if (empty($rawText)) {
        $rawText = $ocrText;
    }

    $judul = $aiData['judul'] ?? null;
    $tahun = !empty($aiData['tahun']) ? (int)$aiData['tahun'] : null;
    $nomorDokumen = $aiData['nomor_dokumen'] ?? null;
    $jenisArsip = $aiData['jenis_arsip'] ?? null;
    $jenisKegiatan = $aiData['jenis_kegiatan'] ?? null;
    $namaKegiatan = $aiData['nama_kegiatan'] ?? null;
    $instansi = $aiData['instansi'] ?? null;
    $dataTerstruktur = $aiData['data_terstruktur'] ?? null;

    if (empty($judul) || empty($tahun) || empty($jenisKegiatan)) {
        $metadata = extractMetadataFromText($rawText);
        $judul = $judul ?: $metadata['judul'];
        $tahun = $tahun ?: $metadata['tahun'];
        $jenisArsip = $jenisArsip ?: $metadata['jenis_arsip'];
        $jenisKegiatan = $jenisKegiatan ?: $metadata['jenis_kegiatan'];
        $nomorDokumen = $nomorDokumen ?: $metadata['nomor_dokumen'];
        $namaKegiatan = $namaKegiatan ?: $metadata['nama_kegiatan'];
        $instansi = $instansi ?: $metadata['instansi'];
    }

    if (empty($judul)) {
        $judul = pathinfo($doc['file_name'], PATHINFO_FILENAME);
    }

    $aiJson = json_encode($aiData);
    $dataTerstrukturJson = $dataTerstruktur !== null ? json_encode($dataTerstruktur, JSON_UNESCAPED_UNICODE) : null;

    // Update DB
    $upd = $conn->prepare('UPDATE archives SET judul=?, nomor_dokumen=?, tahun=?, jenis_arsip=?, jenis_kegiatan=?, nama_kegiatan=?, instansi=?, ocr_text=?, raw_text=?, ai_json=?, data_terstruktur=?, status=? WHERE id=?');
    $done = 'done';
    $upd->bind_param('ssisssssssssi', $judul, $nomorDokumen, $tahun, $jenisArsip, $jenisKegiatan, $namaKegiatan, $instansi, $rawText, $rawText, $aiJson, $dataTerstrukturJson, $done, $id);
    $upd->execute();
    $upd->close();

    echo json_encode(['success' => true, 'id' => $id]);
    exit;
}

// Otherwise show processing page
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memproses Arsip — Arsip Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="app-body">
    <nav class="navbar app-nav sticky-top">
        <div class="container py-2">
            <a class="nav-brand" href="index.php">
                <div class="nav-brand-icon"><i class="bi bi-file-earmark-richtext"></i></div>
                Arsip Digital
            </a>
        </div>
    </nav>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="text-center mb-4 fade-in">
                    <div style="width:80px;height:80px;border-radius:50%;background:rgba(79,70,229,0.15);border:2px solid rgba(79,70,229,0.3);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px">
                        <i class="bi bi-cpu" style="font-size:2rem;color:var(--primary-light);animation:spin 2s linear infinite"></i>
                    </div>
                    <h1 style="font-family:'Inter',sans-serif;font-size:1.6rem;font-weight:900;color:var(--text-white)">Memproses Dokumen...</h1>
                    <p style="color:var(--text-muted)">AI sedang menganalisis dokumen Anda</p>
                </div>

                <div class="glass-card fade-in">
                    <!-- File Info -->
                    <div style="display:flex;align-items:center;gap:12px;padding:14px;border-radius:var(--radius-sm);background:var(--card-bg);border:1px solid var(--border);margin-bottom:24px">
                        <div style="width:42px;height:42px;border-radius:10px;background:rgba(79,70,229,0.15);display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:var(--primary-light);flex-shrink:0">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:0.88rem;color:var(--text-white)"><?php echo htmlspecialchars($doc['file_name']); ?></div>
                            <div style="font-size:0.78rem;color:var(--text-muted)">Format output: <?php echo strtoupper($doc['output_format'] ?? 'PDF'); ?></div>
                        </div>
                    </div>

                    <!-- Processing Steps -->
                    <div class="process-steps" id="processSteps">
                        <div class="process-step" data-step="1">
                            <div class="step-num">1</div>
                            <div>
                                <div class="step-text">Membaca dokumen sumber</div>
                                <div class="step-desc">Mengupload dan mempersiapkan file</div>
                            </div>
                            <div class="step-status" id="status1"></div>
                        </div>
                        <div class="process-step" data-step="2">
                            <div class="step-num">2</div>
                            <div>
                                <div class="step-text">Mengenali teks dengan OCR</div>
                                <div class="step-desc">Mengekstrak teks dari gambar/dokumen</div>
                            </div>
                            <div class="step-status" id="status2"></div>
                        </div>
                        <div class="process-step" data-step="3">
                            <div class="step-num">3</div>
                            <div>
                                <div class="step-text">Menganalisis isi dokumen</div>
                                <div class="step-desc">AI membaca dan memahami konten</div>
                            </div>
                            <div class="step-status" id="status3"></div>
                        </div>
                        <div class="process-step" data-step="4">
                            <div class="step-num">4</div>
                            <div>
                                <div class="step-text">Membuat format output</div>
                                <div class="step-desc">Menyusun dokumen sesuai template</div>
                            </div>
                            <div class="step-status" id="status4"></div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="progress-bar-custom">
                        <div class="progress-bar-fill" id="progressFill" style="width:0%"></div>
                    </div>
                    <div class="text-center mt-3" id="progressText" style="font-size:0.82rem;color:var(--text-muted)">Memulai proses...</div>
                    <div class="text-center mt-4" id="processHelp" style="font-size:0.9rem;color:var(--text-muted);">
                        Setelah proses selesai, Anda akan diarahkan ke halaman hasil dokumen. Jika tidak otomatis, klik tombol di bawah.
                    </div>
                    <div class="text-center mt-3">
                        <a href="detail.php?id=<?php echo $id; ?>" class="btn btn-outline-app btn-sm" id="viewResultButton" style="display:none"><i class="bi bi-eye"></i> Lihat Hasil</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    const docId = <?php echo $id; ?>;
    const steps = document.querySelectorAll('.process-step');
    const progress = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');

    async function runProcessing() {
        // Step 1: Reading document
        activateStep(0);
        progress.style.width = '10%';
        progressText.textContent = 'Membaca dokumen sumber...';
        await delay(800);
        completeStep(0);
        progress.style.width = '25%';

        // Step 2: OCR
        activateStep(1);
        progressText.textContent = 'Mengenali teks dengan OCR...';
        await delay(600);
        completeStep(1);
        progress.style.width = '40%';

        // Step 3: AI Analysis (this is the real API call)
        activateStep(2);
        progressText.textContent = 'AI menganalisis dokumen...';
        
        try {
            const res = await fetch('process.php?id=' + docId + '&action=run');
            const data = await res.json();
            
            completeStep(2);
            progress.style.width = '75%';

            // Step 4: Formatting
            activateStep(3);
            progressText.textContent = 'Membuat format output...';
            await delay(1000);
            completeStep(3);
            progress.style.width = '100%';

            progressText.textContent = '✅ Dokumen berhasil diproses!';
            progressText.style.color = 'var(--success)';
            progressText.style.fontWeight = '700';
            document.getElementById('viewResultButton').style.display = 'inline-block';

            await delay(1200);
            window.location.href = 'detail.php?id=' + docId;

        } catch(e) {
            progressText.textContent = '❌ Terjadi kesalahan: ' + e.message;
            progressText.style.color = 'var(--danger)';
            document.getElementById('viewResultButton').style.display = 'inline-block';
        }
    }

    function activateStep(idx) { steps[idx].classList.add('active'); steps[idx].classList.remove('done'); document.getElementById('status'+(idx+1)).innerHTML='<i class="bi bi-arrow-repeat" style="color:var(--primary-light);animation:spin 1s linear infinite"></i>'; }
    function completeStep(idx) { steps[idx].classList.remove('active'); steps[idx].classList.add('done'); document.getElementById('status'+(idx+1)).innerHTML='<i class="bi bi-check-circle-fill" style="color:var(--success)"></i>'; }
    function delay(ms) { return new Promise(r=>setTimeout(r,ms)); }

    // Start processing
    runProcessing();
    </script>
</body>
</html>
