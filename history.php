<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();
$currentUser = getCurrentUser();

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;
$filterYear = !empty($_GET['tahun']) ? (int)$_GET['tahun'] : null;
$searchJudul = !empty($_GET['judul']) ? trim($_GET['judul']) : null;

// Count total
$whereClause = ($currentUser['role'] === 'admin') ? '1=1' : 'a.user_id = ?';
$params = [];
$types = '';
if ($currentUser['role'] !== 'admin') {
    $params[] = $currentUser['id'];
    $types .= 'i';
}

// Fetch available years for the dropdown
$availableYears = [];
$yearSql = "SELECT DISTINCT tahun FROM archives a WHERE $whereClause AND tahun IS NOT NULL ORDER BY tahun DESC";
$yearStmt = $conn->prepare($yearSql);
if (!empty($params)) {
    $yearStmt->bind_param($types, ...$params);
}
$yearStmt->execute();
$yearRes = $yearStmt->get_result();
while ($yRow = $yearRes->fetch_assoc()) {
    $availableYears[] = $yRow['tahun'];
}
$yearStmt->close();
if ($filterYear) {
    $whereClause .= ' AND a.tahun = ?';
    $params[] = $filterYear;
    $types .= 'i';
}
if ($searchJudul) {
    $whereClause .= ' AND a.judul LIKE ?';
    $params[] = "%$searchJudul%";
    $types .= 's';
}

$countSql = "SELECT COUNT(*) AS total FROM archives a WHERE $whereClause";
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = max(1, ceil($total / $perPage));

// Fetch documents with user info
$querySql = "SELECT a.*, u.full_name AS uploader_name FROM archives a LEFT JOIN users u ON a.user_id = u.id WHERE $whereClause ORDER BY a.uploaded_at DESC LIMIT ? OFFSET ?";
$query = $conn->prepare($querySql);
$queryParams = array_merge($params, [$perPage, $offset]);
$queryTypes = $types . 'ii';
$query->bind_param($queryTypes, ...$queryParams);
$query->execute();
$docs = $query->get_result();
$query->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Arsip — Arsip Digital</title>
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
                <a href="index.php" class="btn btn-primary-app btn-sm"><i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline">Dokumen Baru</span></a>
                <div class="nav-user"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div>
            </div>
        </div>
    </nav>

    <main class="container py-4 py-lg-5">
        <div class="card bg-dark border-0 mb-4 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div>
                        <h1 class="h3 text-white mb-1">Riwayat Arsip</h1>
                        <p class="text-muted mb-0">Tinjau dan kelola arsip digital Anda dengan cepat.</p>
                    </div>
                    <span class="badge rounded-pill bg-primary fs-6 py-2 px-3"><?php echo $total; ?> Arsip Tersimpan</span>
                </div>
            </div>
        </div>

        <div class="card border-0 mb-4 shadow-sm">
            <div class="card-body p-4">
                <?php if ($docs->num_rows > 0):
                    $qYear = $filterYear ? '&tahun='.$filterYear : '';
                    $qJudul = $searchJudul ? '&judul='.urlencode($searchJudul) : '';
                    $qFilter = $qYear.$qJudul;
                ?>
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-3">
                    <div class="btn-group" role="group" aria-label="Tampilan eksport">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-table"></i> Table
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="export.php?mode=simple&tampilan=table&format=excel<?php echo $qFilter; ?>"><i class="bi bi-file-earmark-spreadsheet text-success"></i> Excel</a></li>
                                <li><a class="dropdown-item" href="export.php?mode=simple&tampilan=table&format=pdf<?php echo $qFilter; ?>"><i class="bi bi-file-earmark-pdf text-danger"></i> PDF</a></li>
                            </ul>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-list-ul"></i> List
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="export.php?mode=simple&tampilan=list&format=word<?php echo $qFilter; ?>"><i class="bi bi-file-earmark-word text-primary"></i> Word</a></li>
                                <li><a class="dropdown-item" href="export.php?mode=simple&tampilan=list&format=pdf<?php echo $qFilter; ?>"><i class="bi bi-file-earmark-pdf text-danger"></i> PDF</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <form method="get">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label class="form-label text-uppercase text-muted small">Tahun</label>
                            <select name="tahun" class="form-select form-select-lg bg-dark text-white border-secondary">
                                <option value="">Semua Tahun</option>
                                <?php foreach ($availableYears as $y): ?>
                                <option value="<?php echo htmlspecialchars($y); ?>" <?php echo $filterYear === (int)$y ? 'selected' : ''; ?>><?php echo htmlspecialchars($y); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-5">
                            <label class="form-label text-uppercase text-muted small">Judul</label>
                            <input type="text" name="judul" class="form-control form-control-lg bg-dark text-white border-secondary" placeholder="Cari berdasarkan judul..." value="<?php echo htmlspecialchars($searchJudul ?? ''); ?>">
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <button type="submit" class="btn btn-primary btn-lg w-100">Cari Tahun</button>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <?php if ($filterYear || $searchJudul): ?>
                            <a href="history.php" class="btn btn-outline-secondary btn-lg w-100">Reset</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($docs->num_rows > 0): ?>
        
        <?php if ($filterYear): ?>
        <h3 class="text-center text-white mb-3">Tahun <?php echo htmlspecialchars($filterYear); ?></h3>
        <?php else: ?>
        <h3 class="text-center text-white mb-3">Semua Tahun</h3>
        <?php endif; ?>
        
        <div class="text-center mb-4 fade-in">
            <p class="text-muted small mb-2">Download Daftar Judul & Perihal:</p>
            <a href="export.php?mode=simple&tampilan=table&format=excel<?php echo $qFilter; ?>" class="btn btn-success btn-sm me-1"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</a>
            <a href="export.php?mode=simple&tampilan=table&format=pdf<?php echo $qFilter; ?>" class="btn btn-danger btn-sm me-1"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
            <a href="export.php?mode=simple&tampilan=list&format=word<?php echo $qFilter; ?>" class="btn btn-primary btn-sm"><i class="bi bi-file-earmark-word"></i> Word</a>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4 mb-4">
            <?php while ($d = $docs->fetch_assoc()):
                $dExt = strtolower(pathinfo($d['file_name'], PATHINFO_EXTENSION));
                $dIsImage = in_array($dExt, ['jpg','jpeg','png']);
                $formatClass = match($d['output_format'] ?? 'pdf') { 'word' => 'word', 'excel' => 'excel', default => 'pdf' };
                $formatLabel = match($d['output_format'] ?? 'pdf') { 'word' => 'DOCX', 'excel' => 'XLSX', default => 'PDF' };
            ?>
            <div class="col">
                <div class="card h-100 bg-dark border-secondary shadow-sm">
                    <div class="ratio ratio-4x3 bg-secondary overflow-hidden">
                        <?php if ($dIsImage): ?>
                        <img src="<?php echo htmlspecialchars($d['file_path']); ?>" alt="" class="object-fit-cover">
                        <?php else: ?>
                        <div class="d-flex h-100 w-100 align-items-center justify-content-center text-white opacity-75">
                            <i class="bi bi-file-earmark-text display-4"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title text-white mb-2 text-truncate-2"><?php echo htmlspecialchars($d['judul'] ?? $d['file_name']); ?></h5>
                        <div class="d-flex flex-wrap gap-2 mb-3 text-muted small">
                            <span class="badge bg-secondary bg-opacity-50"><i class="bi bi-calendar3 me-1"></i><?php echo date('d/m/Y', strtotime($d['uploaded_at'])); ?></span>
                            <?php if (!empty($d['tahun'])): ?><span class="badge bg-secondary bg-opacity-50"><i class="bi bi-hash me-1"></i><?php echo $d['tahun']; ?></span><?php endif; ?>
                            <?php if ($currentUser['role'] === 'admin' && !empty($d['uploader_name'])): ?>
                            <span class="badge bg-secondary bg-opacity-50"><i class="bi bi-person me-1"></i><?php echo htmlspecialchars($d['uploader_name']); ?></span>
                            <?php endif; ?>
                            <span class="badge <?php echo $formatClass === 'pdf' ? 'bg-danger bg-opacity-15 text-danger' : ($formatClass === 'word' ? 'bg-primary bg-opacity-15 text-primary' : 'bg-success bg-opacity-15 text-success'); ?>"><?php echo $formatLabel; ?></span>
                        </div>
                        <div class="mt-auto d-flex flex-wrap gap-2">
                            <a href="detail.php?id=<?php echo $d['id']; ?>" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-eye"></i> Lihat</a>
                            <a href="<?php echo htmlspecialchars($d['file_path']); ?>" download class="btn btn-outline-info btn-sm"><i class="bi bi-cloud-download"></i> File Asli</a>
                            <a href="export.php?id=<?php echo $d['id']; ?>&format=<?php echo $d['output_format'] ?? 'pdf'; ?>" class="btn btn-outline-light btn-sm"><i class="bi bi-download"></i> Hasil</a>
                            <?php if ($currentUser['role'] === 'admin'): ?>
                            <form action="delete.php" method="post" class="m-0">
                                <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash3"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <?php if ($totalPages > 1):
            $filterParams = '';
            if ($filterYear) $filterParams .= '&tahun='.$filterYear;
            if ($searchJudul) $filterParams .= '&judul='.urlencode($searchJudul);
        ?>
        <div class="rh-pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1 . $filterParams; ?>" class="rh-page"><i class="bi bi-chevron-left"></i></a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i . $filterParams; ?>" class="rh-page <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1 . $filterParams; ?>" class="rh-page"><i class="bi bi-chevron-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="rh-empty fade-in">
            <div class="rh-empty-icon"><i class="bi bi-inbox"></i></div>
            <h3>Belum Ada Dokumen</h3>
            <p>Anda belum pernah memproses dokumen. Mulai dengan mengupload dokumen pertama Anda.</p>
            <a href="index.php" class="rh-btn rh-btn-primary" style="margin-top:16px"><i class="bi bi-plus-lg"></i> Buat Dokumen Baru</a>
        </div>
        <?php endif; ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
