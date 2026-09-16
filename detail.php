<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'helpers/csrf.php';
requireLogin();
$currentUser = getCurrentUser();

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM archives WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$doc) { header('Location: index.php'); exit; }

// Enforce access control: Only owner or admin can view this document
if ($currentUser['role'] !== 'admin' && $doc['user_id'] !== $currentUser['id']) {
    header('Location: history.php');
    exit;
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    // Validate CSRF token
    if (!validateCsrfToken()) {
        die('CSRF validation failed');
    }
    
    // Only Admin can edit
    if ($currentUser['role'] !== 'admin') {
        die('Unauthorized access. Only admin can edit.');
    }
    $judul = htmlspecialchars(trim($_POST['judul'] ?? ''), ENT_QUOTES, 'UTF-8');
    $nomorDokumen = htmlspecialchars(trim($_POST['nomor_dokumen'] ?? ''), ENT_QUOTES, 'UTF-8');
    $tahun = !empty($_POST['tahun']) ? (int)$_POST['tahun'] : null;
    $jenisArsip = htmlspecialchars(trim($_POST['jenis_arsip'] ?? ''), ENT_QUOTES, 'UTF-8');
    $jenis = htmlspecialchars(trim($_POST['jenis_kegiatan'] ?? ''), ENT_QUOTES, 'UTF-8');
    $namaKegiatan = htmlspecialchars(trim($_POST['nama_kegiatan'] ?? ''), ENT_QUOTES, 'UTF-8');
    $instansi = htmlspecialchars(trim($_POST['instansi'] ?? ''), ENT_QUOTES, 'UTF-8');

    $upd = $conn->prepare('UPDATE archives SET judul=?, nomor_dokumen=?, tahun=?, jenis_arsip=?, jenis_kegiatan=?, nama_kegiatan=?, instansi=? WHERE id=?');
    $upd->bind_param('ssissssi', $judul, $nomorDokumen, $tahun, $jenisArsip, $jenis, $namaKegiatan, $instansi, $id);
    $upd->execute();
    $upd->close();
    header('Location: detail.php?id='.$id.'&saved=1');
    exit;
}

// Initialize CSRF token
initializeCsrfToken();

$aiData = [];
if (!empty($doc['ai_json'])) $aiData = json_decode($doc['ai_json'], true) ?? [];
$ext = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
$isImage = in_array($ext, ['jpg','jpeg','png']);
$saved = isset($_GET['saved']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Arsip — <?php echo htmlspecialchars($doc['judul'] ?? $doc['file_name']); ?></title>
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
            <div class="nav-actions">
                <a href="index.php" class="btn btn-outline-app btn-sm"><i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline">Baru</span></a>
                <a href="history.php" class="btn btn-outline-app btn-sm"><i class="bi bi-clock-history"></i></a>
                <div class="nav-user"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div>
            </div>
        </div>
    </nav>

    <main class="container py-4 py-lg-5">
        <?php if ($saved): ?>
        <div class="alert" style="background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.3);border-radius:var(--radius-sm);color:var(--text-white);display:flex;align-items:center;gap:10px;margin-bottom:20px">
            <i class="bi bi-check-circle" style="color:var(--success)"></i> Perubahan berhasil disimpan!
        </div>
        <?php endif; ?>

        <!-- Flow Diagram -->
        <div class="text-center mb-4 fade-in" style="display:flex;align-items:center;justify-content:center;gap:12px;flex-wrap:wrap;color:var(--text-muted);font-size:0.82rem;font-weight:600">
            <span style="padding:6px 14px;border-radius:50px;background:rgba(79,70,229,0.15);border:1px solid rgba(79,70,229,0.3);color:var(--primary-light)"><i class="bi bi-file-earmark-text"></i> Sumber</span>
            <i class="bi bi-arrow-right"></i>
            <span style="padding:6px 14px;border-radius:50px;background:rgba(6,182,212,0.15);border:1px solid rgba(6,182,212,0.3);color:var(--accent)"><i class="bi bi-cpu"></i> AI</span>
            <i class="bi bi-arrow-right"></i>
            <?php if (!empty($doc['template_path'])): ?>
            <span style="padding:6px 14px;border-radius:50px;background:rgba(124,58,237,0.15);border:1px solid rgba(124,58,237,0.3);color:#A78BFA"><i class="bi bi-palette"></i> Template</span>
            <i class="bi bi-arrow-right"></i>
            <?php endif; ?>
            <span style="padding:6px 14px;border-radius:50px;background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.3);color:var(--success)"><i class="bi bi-check-circle"></i> Hasil</span>
        </div>

        <div class="row g-4">
            <!-- Preview Column -->
            <div class="col-lg-5 fade-in">
                <div class="glass-card" style="position:sticky;top:92px">
                    <?php if ($isImage): ?>
                    <div class="result-preview">
                        <img src="<?php echo htmlspecialchars($doc['file_path']); ?>" alt="Dokumen">
                    </div>
                    <?php else: ?>
                    <div class="result-preview" style="padding:40px">
                        <i class="bi bi-file-earmark-text" style="font-size:4rem;color:var(--primary-light);opacity:0.5"></i>
                        <p style="color:var(--text-muted);margin-top:14px;font-size:0.88rem"><?php echo htmlspecialchars($doc['file_name']); ?></p>
                    </div>
                    <?php endif; ?>
                    <div class="result-info">
                        <div class="info-chip"><i class="bi bi-calendar3"></i> <?php echo htmlspecialchars($doc['tahun'] ?? '-'); ?></div>
                        <div class="info-chip"><i class="bi bi-tag"></i> <?php echo htmlspecialchars($doc['jenis_kegiatan'] ?? '-'); ?></div>
                        <div class="info-chip"><i class="bi bi-clock"></i> <?php echo date('d/m/Y H:i', strtotime($doc['uploaded_at'])); ?></div>
                    </div>
                </div>
            </div>

            <!-- Info Column -->
            <div class="col-lg-7">
                <div class="d-flex flex-column gap-4">
                    <!-- Title & Meta -->
                    <div class="glass-card fade-in fade-in-1">
                        <div class="section-head">
                            <div class="section-icon green"><i class="bi bi-check-circle"></i></div>
                            <div>
                                <h2><?php echo htmlspecialchars($doc['judul'] ?? $doc['file_name']); ?></h2>
                                <p>Dokumen berhasil diproses oleh AI</p>
                            </div>
                        </div>
                        <?php if (!empty($aiData['ringkasan'])): ?>
                        <div style="padding:14px;border-radius:var(--radius-sm);background:rgba(16,185,129,0.06);border:1px solid rgba(16,185,129,0.15);color:var(--text-muted);font-size:0.88rem;line-height:1.6">
                            <strong style="color:var(--success)">Ringkasan AI:</strong> <?php echo htmlspecialchars($aiData['ringkasan']); ?>
                        </div>
                        <?php endif; ?>
                        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:16px">
                            <div style="padding:16px;border-radius:var(--radius-sm);background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                                <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px">Nomor Dokumen</div>
                                <div style="font-weight:600;color:var(--text-white);"><?php echo htmlspecialchars($doc['nomor_dokumen'] ?? '-'); ?></div>
                            </div>
                            <div style="padding:16px;border-radius:var(--radius-sm);background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                                <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px">Jenis Arsip</div>
                                <div style="font-weight:600;color:var(--text-white);"><?php echo htmlspecialchars($doc['jenis_arsip'] ?? '-'); ?></div>
                            </div>
                            <div style="padding:16px;border-radius:var(--radius-sm);background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                                <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px">Nama Kegiatan</div>
                                <div style="font-weight:600;color:var(--text-white);"><?php echo htmlspecialchars($doc['nama_kegiatan'] ?? '-'); ?></div>
                            </div>
                            <div style="padding:16px;border-radius:var(--radius-sm);background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                                <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px">Instansi</div>
                                <div style="font-weight:600;color:var(--text-white);"><?php echo htmlspecialchars($doc['instansi'] ?? '-'); ?></div>
                            </div>
                        </div>

                    </div>

                    <!-- Download Options -->
                    <div class="glass-card fade-in fade-in-2">
                        <div class="section-head">
                            <div class="section-icon indigo"><i class="bi bi-download"></i></div>
                            <div>
                                <h2>Download Hasil</h2>
                                <p>Pilih format untuk mengunduh dokumen</p>
                            </div>
                        </div>
                        <div class="download-grid">
                            <a href="export.php?id=<?php echo $id; ?>&format=pdf" class="download-card">
                                <div class="dl-icon" style="color:#EF4444"><i class="bi bi-file-earmark-pdf"></i></div>
                                <div class="dl-label">Download PDF</div>
                                <div class="dl-hint">Siap cetak</div>
                            </a>
                            <a href="export.php?id=<?php echo $id; ?>&format=word" class="download-card">
                                <div class="dl-icon" style="color:#3B82F6"><i class="bi bi-file-earmark-word"></i></div>
                                <div class="dl-label">Download Word</div>
                                <div class="dl-hint">Bisa diedit</div>
                            </a>
                            <a href="export.php?id=<?php echo $id; ?>&format=excel" class="download-card">
                                <div class="dl-icon" style="color:#10B981"><i class="bi bi-file-earmark-spreadsheet"></i></div>
                                <div class="dl-label">Download Excel</div>
                                <div class="dl-hint">Data tabel</div>
                            </a>
                        </div>
                    </div>

                    <!-- Edit Form -->
                    <?php if ($currentUser['role'] === 'admin'): ?>
                    <div class="glass-card fade-in fade-in-3">
                        <div class="section-head">
                            <div class="section-icon cyan"><i class="bi bi-pencil-square"></i></div>
                            <div>
                                <h2>Edit Informasi</h2>
                                <p>Perbaiki metadata jika diperlukan</p>
                            </div>
                        </div>
                        <form method="post" class="row g-3">
                            <input type="hidden" name="action" value="edit">
                            <?= getCsrfTokenInput() ?>
                            <div class="col-12">
                                <label class="form-label">Judul</label>
                                <input class="form-control app-input" name="judul" value="<?php echo htmlspecialchars($doc['judul'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor Dokumen</label>
                                <input class="form-control app-input" name="nomor_dokumen" value="<?php echo htmlspecialchars($doc['nomor_dokumen'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tahun</label>
                                <input class="form-control app-input" type="number" name="tahun" value="<?php echo htmlspecialchars($doc['tahun'] ?? ''); ?>" min="1900" max="2099">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis Arsip</label>
                                <input class="form-control app-input" name="jenis_arsip" value="<?php echo htmlspecialchars($doc['jenis_arsip'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis Kegiatan</label>
                                <input class="form-control app-input" name="jenis_kegiatan" value="<?php echo htmlspecialchars($doc['jenis_kegiatan'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nama Kegiatan / Acara</label>
                                <input class="form-control app-input" name="nama_kegiatan" value="<?php echo htmlspecialchars($doc['nama_kegiatan'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Instansi</label>
                                <input class="form-control app-input" name="instansi" value="<?php echo htmlspecialchars($doc['instansi'] ?? ''); ?>">
                            </div>
                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary-app"><i class="bi bi-save2"></i> Simpan</button>
                                <a href="index.php" class="btn btn-outline-app"><i class="bi bi-plus-lg"></i> Buat Baru</a>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>

                    <!-- Delete -->
                    <?php if ($currentUser['role'] === 'admin'): ?>
                    <div class="danger-panel fade-in">
                        <div>
                            <h2><i class="bi bi-exclamation-triangle"></i> Hapus Dokumen</h2>
                            <p>Hapus dokumen ini beserta file dari penyimpanan.</p>
                        </div>
                        <form action="delete.php" method="post" onsubmit="return confirm('Yakin ingin menghapus?')">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <button type="submit" class="btn btn-danger-app"><i class="bi bi-trash3"></i> Hapus</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
