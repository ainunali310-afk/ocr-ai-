<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'config.php';
require_once 'auth.php';
require_once 'helpers/csrf.php';
requireLogin();
$currentUser = getCurrentUser();
initializeCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0F172A">
    <title>Arsip Digital — Scan, OCR, AI Ekstraksi</title>
    <meta name="description" content="Unggah arsip fisik atau digital, lakukan OCR, ekstrak metadata otomatis, dan simpan sebagai arsip digital terstruktur.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css?v=20260713">
    <style>
        /* Touch-friendly adjustments */
        @supports (padding: max(0px)) {
            body.app-body { padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom); }
            .app-nav { padding-top: env(safe-area-inset-top); }
            .toast-box { right: max(12px, env(safe-area-inset-right)); left: max(12px, env(safe-area-inset-left)); }
        }
        /* Ensure buttons are at least 48px for touch targets */
        .btn { min-height: 44px; }
        /* Reduce double-tap zoom on inputs */
        input, select, textarea { font-size: 16px !important; }
    </style>
</head>
<body class="app-body">
    <!-- Navbar -->
    <nav class="navbar app-nav sticky-top">
        <div class="container py-2">
            <a class="nav-brand" href="index.php">
                <div class="nav-brand-icon"><i class="bi bi-file-earmark-richtext"></i></div>
                <span class="d-none d-sm-inline">Arsip Digital</span>
                <span class="d-sm-none">Arsip</span>
            </a>
            <div class="nav-actions">
                <a href="history.php" class="btn btn-outline-app btn-sm" title="Riwayat"><i class="bi bi-clock-history"></i> <span class="d-none d-md-inline">Riwayat</span></a>
                <?php if (isAdmin()): ?>
                <a href="manage.php" class="btn btn-outline-app btn-sm" title="Admin"><i class="bi bi-gear"></i></a>
                <?php endif; ?>
                <div class="d-none d-md-block text-end">
                    <div class="nav-user-name"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                    <div class="nav-user-role"><?php echo $currentUser['role']; ?></div>
                </div>
                <div class="dropdown">
                    <button class="nav-user" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background: linear-gradient(135deg, var(--primary), var(--accent)); border: none; cursor: pointer;" title="Menu user"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></button>
                    <ul class="dropdown-menu dropdown-menu-end" style="background:rgba(15,23,42,0.95);border:1px solid rgba(255,255,255,0.1);backdrop-filter:blur(16px)">
                        <li><span class="dropdown-item-text" style="color:rgba(255,255,255,0.5);font-size:0.82rem"><?php echo htmlspecialchars($currentUser['username']); ?></span></li>
                        <li><hr class="dropdown-divider" style="border-color:rgba(255,255,255,0.08)"></li>
                        <li><a class="dropdown-item" style="color:#fff" href="history.php"><i class="bi bi-clock-history me-2"></i>Riwayat</a></li>
                        <?php if (isAdmin()): ?><li><a class="dropdown-item" style="color:#fff" href="manage.php"><i class="bi bi-gear me-2"></i>Admin</a></li><?php endif; ?>
                        <li><a class="dropdown-item" style="color:#EF4444" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div id="toastBox" class="toast-box"></div>

    <!-- Camera Modal -->
    <div id="cameraModal" class="camera-overlay">
        <div class="camera-box">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                <h3 style="margin:0; display:flex; align-items:center; gap:10px;"><i class="bi bi-camera-video" style="color:var(--accent)"></i> Ambil Foto Dokumen</h3>
                <button type="button" class="btn-close btn-close-white" onclick="closeCamera()" style="background:none; border:none; cursor:pointer; opacity:0.7;" aria-label="Tutup"></button>
            </div>
            <video id="cameraVideo" autoplay playsinline style="width:100%; border-radius:12px; border:1px solid var(--border); background:#000;"></video>
            <canvas id="cameraCanvas" style="display:none"></canvas>
            <div id="cameraPreviewBox" style="display:none"><img id="capturedImage" style="width:100%;border-radius:12px;border:1px solid var(--border)"></div>
            <div class="camera-controls">
                <button type="button" class="btn btn-outline-app" onclick="switchCamera()" title="Putar kamera"><i class="bi bi-arrow-repeat"></i> <span class="d-none d-sm-inline">Putar</span></button>
                <button type="button" class="btn-shutter" id="captureBtn" onclick="capturePhoto()" title="Ambil foto"><i class="bi bi-camera"></i></button>
                <button type="button" class="btn btn-outline-app" onclick="closeCamera()" title="Tutup"><i class="bi bi-x-lg"></i> <span class="d-none d-sm-inline">Tutup</span></button>
            </div>
            <div id="cameraActions" style="display:none;margin-top:14px;text-align:center">
                <button type="button" class="btn btn-primary-app" onclick="useCapturedPhoto()"><i class="bi bi-check-lg"></i> <span class="d-none d-sm-inline">Gunakan Foto</span><span class="d-sm-none">Gunakan</span></button>
                <button type="button" class="btn btn-outline-app" onclick="retakePhoto()"><i class="bi bi-arrow-counterclockwise"></i> <span class="d-none d-sm-inline">Ulang</span></button>
            </div>
        </div>
    </div>

    <main class="container py-4 page-shell">
        <!-- Hero -->
        <section class="hero-section fade-in">
            <div class="hero-badge"><i class="bi bi-cpu"></i> Ditenagai AI Gemini</div>
            <h1 class="hero-title">Digitalisasi Arsip<br>dengan <span>OCR & AI</span></h1>
            <p class="hero-desc">Scan / foto arsip, ekstrak metadata otomatis, klasifikasikan dokumen, dan simpan dalam arsip digital yang mudah dicari.</p>
            <div class="hero-highlights">
                <div class="hero-pill"><i class="bi bi-camera-reels"></i> Scan / Foto Arsip</div>
                <div class="hero-pill"><i class="bi bi-text-paragraph"></i> OCR & Ekstraksi Data</div>
                <div class="hero-pill"><i class="bi bi-search"></i> Cari Berdasarkan Tahun</div>
            </div>
        </section>

        <div class="workflow-card fade-in">
            <div class="workflow-step">
                <div class="workflow-number">1</div>
                <div>
                    <h3>Upload / Foto Arsip</h3>
                    <p>Unggah gambar atau dokumen arsip untuk digitalisasi.</p>
                </div>
            </div>
            <div class="workflow-step">
                <div class="workflow-number">2</div>
                <div>
                    <h3>OCR & Ekstraksi</h3>
                    <p>AI akan membaca teks dan ekstrak metadata penting.</p>
                </div>
            </div>
            <div class="workflow-step">
                <div class="workflow-number">3</div>
                <div>
                    <h3>Simpan & Cari</h3>
                    <p>Arsip tersimpan dengan metadata untuk pencarian tahun.</p>
                </div>
            </div>
        </div>

        <form action="upload.php" method="post" enctype="multipart/form-data" id="mainForm">
        <?= getCsrfTokenInput() ?>
        <div class="row g-4 mb-4 justify-content-center">
            <div class="col-12 col-lg-8 fade-in fade-in-1">
                <div class="glass-card h-100">
                    <div class="section-head">
                        <div class="section-icon indigo"><i class="bi bi-file-earmark-text"></i></div>
                        <div>
                            <h2>📄 Dokumen Sumber</h2>
                            <p>Unggah dokumen yang ingin dikonversi menjadi arsip digital.</p>
                        </div>
                    </div>
                    <div class="upload-zone" id="sourceZone">
                        <div class="drop-icon" style="color:var(--primary-light)"><i class="bi bi-cloud-arrow-up"></i></div>
                        <div class="drop-title">Seret file ke sini</div>
                        <div class="drop-hint">JPG, PNG, PDF, DOCX, XLSX</div>
                        <input type="file" id="sourceFile" name="source_doc" accept="image/*,.pdf,.docx,.doc,.xlsx,.xls" required>
                        <div class="upload-btns">
                            <button type="button" class="btn btn-primary-app" onclick="document.getElementById('sourceFile').click()" title="Pilih file dari perangkat"><i class="bi bi-folder2-open"></i> <span class="d-none d-sm-inline">Pilih File</span><span class="d-sm-none">Pilih</span></button>
                            <button type="button" class="btn btn-outline-app" onclick="openCamera('source')" title="Ambil foto dokumen"><i class="bi bi-camera"></i> <span class="d-none d-sm-inline">Kamera</span></button>
                        </div>
                    </div>
                    <div class="file-preview" id="sourcePreview">
                        <div class="file-icon" id="sourceIcon"><i class="bi bi-file-earmark"></i></div>
                        <div class="file-info">
                            <div class="file-name" id="sourceName">—</div>
                            <div class="file-meta" id="sourceMeta">—</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generate Button -->
        <div class="text-center mb-5 fade-in">
            <button type="submit" class="btn btn-primary-app btn-lg" id="generateBtn" disabled title="Mulai digitalisasi arsip">
                <i class="bi bi-cpu"></i> <span class="d-none d-sm-inline">Digitalisasi Arsip</span><span class="d-sm-none">Digitalisasi</span>
            </button>
            <p style="color:var(--text-dim);font-size:0.82rem;margin-top:10px">Minimal upload dokumen sumber untuk memulai</p>
        </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script>
    // ========== MOBILE DETECTION & OPTIMIZATION ==========
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    
    // Prevent zoom on input focus
    if (isMobile) {
        document.addEventListener('touchmove', function(e) {
            if (e.touches.length > 1) e.preventDefault();
        }, { passive: false });
    }

    // ========== DRAG & DROP ==========
    let activeTarget = 'source'; // for camera
    function setupDropZone(zoneId, inputId, previewId, iconId, nameId, metaId) {
        const zone = document.getElementById(zoneId);
        const input = document.getElementById(inputId);
        
        // Desktop drag & drop
        ['dragenter','dragover'].forEach(e => zone.addEventListener(e, ev => { 
            ev.preventDefault(); 
            zone.classList.add('drag-over'); 
        }));
        ['dragleave','drop'].forEach(e => zone.addEventListener(e, ev => { 
            ev.preventDefault(); 
            zone.classList.remove('drag-over'); 
        }));
        zone.addEventListener('drop', ev => {
            const f = ev.dataTransfer.files;
            if (f.length > 0) { input.files = f; showPreview(f[0], previewId, iconId, nameId, metaId, zoneId); }
        });
        
        // File input change
        input.addEventListener('change', () => { 
            if (input.files.length > 0) showPreview(input.files[0], previewId, iconId, nameId, metaId, zoneId); 
        });
        
        // Mobile tap to upload
        zone.addEventListener('click', () => input.click());
    }

    function showPreview(file, previewId, iconId, nameId, metaId, zoneId) {
        const ext = file.name.split('.').pop().toLowerCase();
        const icons = { jpg:'bi-file-earmark-image', jpeg:'bi-file-earmark-image', png:'bi-file-earmark-image', pdf:'bi-file-earmark-pdf', docx:'bi-file-earmark-word', doc:'bi-file-earmark-word', xlsx:'bi-file-earmark-spreadsheet', xls:'bi-file-earmark-spreadsheet' };
        const colors = { jpg:'#F59E0B', jpeg:'#F59E0B', png:'#F59E0B', pdf:'#EF4444', docx:'#3B82F6', doc:'#3B82F6', xlsx:'#10B981', xls:'#10B981' };
        document.getElementById(iconId).innerHTML = `<i class="bi ${icons[ext]||'bi-file-earmark'}" style="color:${colors[ext]||'#818CF8'}"></i>`;
        document.getElementById(nameId).textContent = file.name;
        document.getElementById(metaId).textContent = (file.size/1024/1024).toFixed(2) + ' MB • ' + ext.toUpperCase();
        document.getElementById(previewId).classList.add('show');
        document.getElementById(zoneId).classList.add('has-file');
        checkReady();
    }

    setupDropZone('sourceZone','sourceFile','sourcePreview','sourceIcon','sourceName','sourceMeta');

    // ========== CHECK READY ==========
    function checkReady() {
        const hasSource = document.getElementById('sourceFile').files.length > 0;
        document.getElementById('generateBtn').disabled = !hasSource;
    }

    // ========== CAMERA ==========
    let stream = null, facingMode = 'environment';
    async function openCamera(target) {
        activeTarget = target;
        document.getElementById('cameraModal').classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent scrolling on mobile
        try {
            const constraints = {
                video: {
                    facingMode,
                    width: { ideal: isMobile ? 1280 : 1920 },
                    height: { ideal: isMobile ? 720 : 1080 }
                },
                audio: false
            };
            stream = await navigator.mediaDevices.getUserMedia(constraints);
            document.getElementById('cameraVideo').srcObject = stream;
            // Auto-focus on iOS
            if (isIOS && stream.getVideoTracks()[0].getCapabilities) {
                try {
                    await stream.getVideoTracks()[0].applyConstraints({ focusMode: ['continuous', 'auto'] });
                } catch (e) { console.log('Auto-focus not supported'); }
            }
        } catch(e) { 
            showToast('Tidak dapat mengakses kamera: '+e.message,'error'); 
            closeCamera(); 
        }
    }
    
    function closeCamera() {
        if (stream) { stream.getTracks().forEach(t=>t.stop()); stream=null; }
        document.getElementById('cameraVideo').srcObject = null;
        document.getElementById('cameraModal').classList.remove('active');
        document.getElementById('cameraPreviewBox').style.display='none';
        document.getElementById('cameraVideo').style.display='block';
        document.getElementById('cameraActions').style.display='none';
        document.getElementById('captureBtn').style.display='flex';
        document.body.style.overflow = ''; // Restore scrolling
    }
    
    async function switchCamera() {
        facingMode = facingMode==='environment'?'user':'environment';
        if(stream){stream.getTracks().forEach(t=>t.stop());}
        try{
            const constraints = {
                video: {
                    facingMode,
                    width: { ideal: isMobile ? 1280 : 1920 },
                    height: { ideal: isMobile ? 720 : 1080 }
                },
                audio: false
            };
            stream = await navigator.mediaDevices.getUserMedia(constraints);
            document.getElementById('cameraVideo').srcObject = stream;
        }catch(e){showToast('Gagal switch kamera','error');}
    }
    
    function capturePhoto() {
        const v=document.getElementById('cameraVideo'),c=document.getElementById('cameraCanvas');
        c.width=v.videoWidth;c.height=v.videoHeight;c.getContext('2d').drawImage(v,0,0);
        document.getElementById('capturedImage').src=c.toDataURL('image/jpeg',0.9);
        v.style.display='none';document.getElementById('cameraPreviewBox').style.display='block';
        document.getElementById('captureBtn').style.display='none';document.getElementById('cameraActions').style.display='block';
    }
    
    function retakePhoto(){
        document.getElementById('cameraPreviewBox').style.display='none';
        document.getElementById('cameraVideo').style.display='block';
        document.getElementById('captureBtn').style.display='flex';
        document.getElementById('cameraActions').style.display='none';
    }
    
    function useCapturedPhoto() {
        const c=document.getElementById('cameraCanvas');
        c.toBlob(blob=>{
            const file=new File([blob],'camera_'+Date.now()+'.jpg',{type:'image/jpeg'});
            const dt=new DataTransfer();dt.items.add(file);
            document.getElementById('sourceFile').files=dt.files;
            showPreview(file,'sourcePreview','sourceIcon','sourceName','sourceMeta','sourceZone');
            closeCamera();
            showToast('Foto berhasil diambil!','success');
        },'image/jpeg',0.9);
    }

    // ========== TOAST ==========
    function showToast(msg,type='info'){
        const icons={info:'bi-info-circle',success:'bi-check-circle',error:'bi-exclamation-triangle'};
        const colors={info:'var(--primary-light)',success:'var(--success)',error:'var(--danger)'};
        const t=document.createElement('div');t.className='toast-item';
        t.innerHTML=`<i class="bi ${icons[type]}" style="color:${colors[type]};font-size:1.1rem"></i> ${msg}`;
        document.getElementById('toastBox').appendChild(t);setTimeout(()=>t.remove(),4000);
    }
    
    // ========== MOBILE VIEWPORT FIX ==========
    // Handle viewport height on mobile (address bar hiding/showing)
    function setVh() {
        let vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', vh + 'px');
    }
    setVh();
    window.addEventListener('resize', setVh);
    window.addEventListener('orientationchange', setVh);
    </script>
</body>
</html>
