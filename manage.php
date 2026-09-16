<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'helpers/csrf.php';
require_once 'helpers/sanitize.php';
requireLogin();

if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$currentUser = getCurrentUser();
$activeTab = $_GET['tab'] ?? 'stats';
$message = '';
$messageType = '';

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken()) {
        die('CSRF validation failed');
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_category') {
        $name = sanitizeAndEscape(trim($_POST['cat_name'] ?? ''));
        $color = $_POST['cat_color'] ?? '#03e6ff';
        $icon = sanitizeAndEscape($_POST['cat_icon'] ?? 'bi-folder');
        
        // Validate color hex code
        if (!isValidHexColor($color)) {
            $color = '#03e6ff';
        }
        
        if (!empty($name)) {
            $stmt = $conn->prepare('INSERT INTO categories (name, color, icon) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $name, $color, $icon);
            $stmt->execute();
            $stmt->close();
            $message = 'Kategori berhasil ditambahkan.';
            $messageType = 'success';
        }
        $activeTab = 'categories';
    } elseif ($action === 'delete_category') {
        $catId = (int)($_POST['cat_id'] ?? 0);
        if ($catId > 0) {
            // Use prepared statement to prevent SQL injection
            $updateStmt = $conn->prepare('UPDATE archives SET category_id = NULL WHERE category_id = ?');
            $updateStmt->bind_param('i', $catId);
            $updateStmt->execute();
            $updateStmt->close();
            
            $deleteStmt = $conn->prepare('DELETE FROM categories WHERE id = ?');
            $deleteStmt->bind_param('i', $catId);
            $deleteStmt->execute();
            $deleteStmt->close();
            $message = 'Kategori berhasil dihapus.';
            $messageType = 'success';
        }
        $activeTab = 'categories';
    } elseif ($action === 'update_user_role') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $newRole = $_POST['new_role'] ?? 'user';
        if ($userId > 0 && $userId !== $currentUser['id'] && in_array($newRole, ['admin', 'user'])) {
            $stmt = $conn->prepare('UPDATE users SET role = ? WHERE id = ?');
            $stmt->bind_param('si', $newRole, $userId);
            $stmt->execute();
            $stmt->close();
            $message = 'Role user berhasil diubah.';
            $messageType = 'success';
        }
        $activeTab = 'users';
    } elseif ($action === 'delete_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId > 0 && $userId !== $currentUser['id']) {
            $updateStmt = $conn->prepare('UPDATE archives SET user_id = NULL WHERE user_id = ?');
            $updateStmt->bind_param('i', $userId);
            $updateStmt->execute();
            $updateStmt->close();
            
            $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
            $message = 'User berhasil dihapus.';
            $messageType = 'success';
        }
        $activeTab = 'users';
    }
}

// Get stats
$totalDocs = $conn->query("SELECT COUNT(*) AS c FROM archives")->fetch_assoc()['c'];
$totalUsers = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$totalCategories = $conn->query("SELECT COUNT(*) AS c FROM categories")->fetch_assoc()['c'];
$thisMonthDocs = $conn->query("SELECT COUNT(*) AS c FROM archives WHERE MONTH(uploaded_at) = MONTH(NOW()) AND YEAR(uploaded_at) = YEAR(NOW())")->fetch_assoc()['c'];

// Get data for tabs
$categories = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM archives WHERE category_id = c.id) AS doc_count FROM categories c ORDER BY c.name ASC");
$users = $conn->query("SELECT u.*, (SELECT COUNT(*) FROM archives WHERE user_id = u.id) AS doc_count FROM users u ORDER BY u.created_at DESC");

// Stats by year
$yearStats = $conn->query("SELECT tahun, COUNT(*) AS total FROM archives WHERE tahun IS NOT NULL GROUP BY tahun ORDER BY tahun DESC LIMIT 10");

// Stats by jenis kegiatan
$jenisStats = $conn->query("SELECT jenis_kegiatan, COUNT(*) AS total FROM archives WHERE jenis_kegiatan IS NOT NULL AND jenis_kegiatan != '' GROUP BY jenis_kegiatan ORDER BY total DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Arsip Nebula</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="app-body">
    <nav class="navbar navbar-expand-lg navbar-dark app-nav sticky-top">
        <div class="container py-2">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
                <img class="brand-logo" src="assets/logo/logo.svg" alt="Logo Arsip Nebula">
                Arsip Nebula
            </a>
            <div class="nav-user-section">
                <a href="index.php" class="btn btn-outline-app btn-sm">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-md-inline">Dashboard</span>
                </a>
                <div class="nav-user-info d-none d-md-block">
                    <div class="nav-user-name"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                    <div class="nav-user-role">Admin</div>
                </div>
                <div class="nav-avatar">
                    <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4 py-lg-5">
        <section class="hero-section text-start mb-4" style="padding:0;text-align:left;">
            <span class="hero-badge"><i class="bi bi-gear"></i> Panel Administrasi</span>
            <h1 class="hero-title mb-2" style="font-size:2rem;text-align:left;">Manajemen Fitur & Data</h1>
            <p class="hero-desc mb-2" style="text-align:left;margin-left:0;">Di sini Anda dapat mengelola seluruh aspek sistem arsip digital: melihat statistik, membangun kategori untuk klasifikasi, dan mengatur pengguna.</p>
            <div class="admin-help-panel">
                <div><strong>Fungsi utama:</strong></div>
                <ul>
                    <li>Statistik: pantau jumlah arsip, pengguna, kategori, dan distribusi per tahun.</li>
                    <li>Kategori: buat atau hapus label kategori untuk klasifikasi dokumen.</li>
                    <li>Pengguna: ubah role admin/user dan hapus akun yang tidak diperlukan.</li>
                </ul>
            </div>
        </section>

        <?php if ($message): ?>
            <div class="alert d-flex align-items-center gap-2" role="alert" style="background:<?php echo $messageType === 'success' ? 'rgba(16,185,129,0.15)' : 'rgba(6,182,212,0.15)'; ?>;border:1px solid <?php echo $messageType === 'success' ? 'rgba(16,185,129,0.3)' : 'rgba(6,182,212,0.3)'; ?>;color:var(--text-white);">
                <i class="bi <?php echo $messageType === 'success' ? 'bi-check-circle' : 'bi-info-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Admin Tabs -->
        <div class="admin-tabs">
            <button class="admin-tab <?php echo $activeTab === 'stats' ? 'active' : ''; ?>" onclick="switchTab(event, 'stats')">
                <i class="bi bi-graph-up"></i> Statistik
            </button>
            <button class="admin-tab <?php echo $activeTab === 'categories' ? 'active' : ''; ?>" onclick="switchTab(event, 'categories')">
                <i class="bi bi-bookmark-star"></i> Kategori
            </button>
            <button class="admin-tab <?php echo $activeTab === 'users' ? 'active' : ''; ?>" onclick="switchTab(event, 'users')">
                <i class="bi bi-people"></i> Pengguna
            </button>
        </div>

        <!-- Stats Panel -->
        <div class="admin-panel <?php echo $activeTab === 'stats' ? 'active' : ''; ?>" id="panel-stats">
            <div class="stat-cards">
                <div class="stat-card">
                    <div class="stat-icon" style="color:var(--nebula-cyan)"><i class="bi bi-file-earmark-text"></i></div>
                    <div class="stat-value"><?php echo $totalDocs; ?></div>
                    <div class="stat-label">Total Dokumen</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color:var(--nebula-pink)"><i class="bi bi-people"></i></div>
                    <div class="stat-value"><?php echo $totalUsers; ?></div>
                    <div class="stat-label">Pengguna</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color:var(--nebula-success)"><i class="bi bi-bookmark-star"></i></div>
                    <div class="stat-value"><?php echo $totalCategories; ?></div>
                    <div class="stat-label">Kategori</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color:var(--nebula-warning)"><i class="bi bi-calendar-check"></i></div>
                    <div class="stat-value"><?php echo $thisMonthDocs; ?></div>
                    <div class="stat-label">Bulan Ini</div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="glass-card">
                        <div class="section-title">
                            <span class="icon-box cyan"><i class="bi bi-calendar3"></i></span>
                            <div><h2>Dokumen per Tahun</h2><p class="section-subtitle">Lihat distribusi arsip berdasarkan tahun terbit atau dokumen.</p></div>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead><tr><th>Tahun</th><th>Jumlah</th><th>Visual</th></tr></thead>
                                <tbody>
                                <?php
                                $maxYearCount = 1;
                                $yearData = [];
                                while ($r = $yearStats->fetch_assoc()) {
                                    $yearData[] = $r;
                                    if ($r['total'] > $maxYearCount) $maxYearCount = $r['total'];
                                }
                                foreach ($yearData as $r):
                                    $pct = round(($r['total'] / $maxYearCount) * 100);
                                ?>
                                <tr>
                                    <td><strong><?php echo $r['tahun'] ?? '-'; ?></strong></td>
                                    <td><?php echo $r['total']; ?></td>
                                    <td>
                                        <div style="background:rgba(3,230,255,0.15);border-radius:6px;height:24px;overflow:hidden;min-width:120px">
                                            <div style="width:<?php echo $pct; ?>%;height:100%;background:linear-gradient(90deg,var(--nebula-cyan),var(--nebula-pink));border-radius:6px;transition:width 0.6s"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($yearData)): ?>
                                <tr><td colspan="3" class="text-center text-white-50">Belum ada data</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-card">
                        <div class="section-title">
                            <span class="icon-box magenta"><i class="bi bi-tag"></i></span>
                            <div><h2>Dokumen per Jenis Kegiatan</h2><p class="section-subtitle">Identifikasi jenis kegiatan teratas untuk klasifikasi arsip.</p></div>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead><tr><th>Jenis Kegiatan</th><th>Jumlah</th></tr></thead>
                                <tbody>
                                <?php
                                $jenisData = [];
                                while ($r = $jenisStats->fetch_assoc()) $jenisData[] = $r;
                                foreach ($jenisData as $r):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['jenis_kegiatan']); ?></td>
                                    <td><span class="category-badge"><?php echo $r['total']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($jenisData)): ?>
                                <tr><td colspan="2" class="text-center text-white-50">Belum ada data</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Panel -->
        <div class="admin-panel <?php echo $activeTab === 'categories' ? 'active' : ''; ?>" id="panel-categories">
            <div class="glass-card mb-4">
                <div class="section-title">
                    <span class="icon-box green"><i class="bi bi-plus-circle"></i></span>
                    <div><h2>Tambah Kategori Baru</h2><p class="section-subtitle">Buat label kategori baru untuk arsip agar hasil ekstraksi AI lebih terstruktur.</p></div>
                </div>
                <form method="post" class="row g-3 align-items-end">
                    <input type="hidden" name="action" value="add_category">
                    <?= getCsrfTokenInput() ?>
                    <div class="col-md-4">
                        <label class="form-label">Nama Kategori</label>
                        <input type="text" class="form-control app-input" name="cat_name" placeholder="Contoh: Surat Masuk" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Warna</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" class="form-control form-control-color" name="cat_color" value="#03e6ff" style="width:50px;height:38px;background:transparent;border:1px solid rgba(255,255,255,0.18);border-radius:8px">
                            <span class="text-white-50" style="font-size:0.82rem">Pilih warna</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Icon (Bootstrap Icons)</label>
                        <select class="form-select app-input" name="cat_icon">
                            <option value="bi-folder">📁 Folder</option>
                            <option value="bi-envelope">✉️ Surat</option>
                            <option value="bi-file-text">📄 Dokumen</option>
                            <option value="bi-book">📖 Buku</option>
                            <option value="bi-camera">📷 Foto</option>
                            <option value="bi-clipboard-data">📋 Laporan</option>
                            <option value="bi-calendar-event">📅 Acara</option>
                            <option value="bi-trophy">🏆 Prestasi</option>
                            <option value="bi-building">🏢 Instansi</option>
                            <option value="bi-cash-stack">💰 Keuangan</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary-app w-100">
                            <i class="bi bi-plus-lg"></i> Tambah
                        </button>
                    </div>
                </form>
            </div>

            <div class="glass-card">
                <div class="section-title">
                    <span class="icon-box cyan"><i class="bi bi-bookmark-star"></i></span>
                    <div>
                        <h2>Daftar Kategori</h2>
                        <p><?php echo $totalCategories; ?> kategori terdaftar</p>
                    </div>
                </div>
                <p class="section-subtitle">Hapus atau ubah kategori yang sudah tidak relevan agar arsip tetap rapi.</p>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr><th>Warna</th><th>Icon</th><th>Nama</th><th>Dokumen</th><th>Dibuat</th><th>Aksi</th></tr>
                        </thead>
                        <tbody>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <tr>
                                <td><span class="color-dot" style="background:<?php echo htmlspecialchars($cat['color']); ?>"></span></td>
                                <td><i class="bi <?php echo htmlspecialchars($cat['icon']); ?>" style="font-size:1.2rem;color:<?php echo htmlspecialchars($cat['color']); ?>"></i></td>
                                <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                <td><span class="category-badge"><?php echo $cat['doc_count']; ?></span></td>
                                <td class="text-white-50"><?php echo date('d/m/Y', strtotime($cat['created_at'])); ?></td>
                                <td>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Hapus kategori ini?')">
                                        <input type="hidden" name="action" value="delete_category">
                                        <input type="hidden" name="cat_id" value="<?php echo $cat['id']; ?>">
                                        <?= getCsrfTokenInput() ?>
                                        <button type="submit" class="btn btn-sm btn-danger-app"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Users Panel -->
        <div class="admin-panel <?php echo $activeTab === 'users' ? 'active' : ''; ?>" id="panel-users">
            <div class="glass-card">
                <div class="section-title">
                    <span class="icon-box magenta"><i class="bi bi-people"></i></span>
                    <div>
                        <h2>Manajemen Pengguna</h2>
                        <p><?php echo $totalUsers; ?> pengguna terdaftar</p>
                    </div>
                </div>
                <p class="section-subtitle">Kelola izin akses: berikan role admin hanya pada akun tepercaya.</p>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr><th>Avatar</th><th>Nama</th><th>Username</th><th>Role</th><th>Dokumen</th><th>Terdaftar</th><th>Aksi</th></tr>
                        </thead>
                        <tbody>
                        <?php while ($u = $users->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="nav-avatar" style="width:32px;height:32px;font-size:0.75rem">
                                        <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                                    </div>
                                </td>
                                <td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
                                <td class="text-white-50"><?php echo htmlspecialchars($u['username']); ?></td>
                                <td>
                                    <span class="category-badge" style="<?php echo $u['role'] === 'admin' ? 'background:rgba(255,0,127,0.2);border-color:rgba(255,0,127,0.4)' : ''; ?>">
                                        <?php echo $u['role'] === 'admin' ? '👑 Admin' : '👤 User'; ?>
                                    </span>
                                </td>
                                <td><?php echo $u['doc_count']; ?></td>
                                <td class="text-white-50"><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                                <td>
                                    <?php if ($u['id'] !== $currentUser['id']): ?>
                                    <div class="d-flex gap-1">
                                        <form method="post" style="display:inline">
                                            <input type="hidden" name="action" value="update_user_role">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <input type="hidden" name="new_role" value="<?php echo $u['role'] === 'admin' ? 'user' : 'admin'; ?>">
                                            <?= getCsrfTokenInput() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-app" title="Ubah role">
                                                <i class="bi bi-arrow-left-right"></i>
                                            </button>
                                        </form>
                                        <form method="post" style="display:inline" onsubmit="return confirm('Hapus user ini? Data arsip miliknya tetap tersimpan.')">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <?= getCsrfTokenInput() ?>
                                            <button type="submit" class="btn btn-sm btn-danger-app"><i class="bi bi-trash3"></i></button>
                                        </form>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-white-50" style="font-size:0.78rem">Anda</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script>
        function switchTab(evt, tabName) {
            document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.admin-panel').forEach(p => p.classList.remove('active'));
            document.getElementById('panel-' + tabName).classList.add('active');
            evt.target.closest('.admin-tab').classList.add('active');
            // Update URL without reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', url);
        }
    </script>
</body>
</html>
