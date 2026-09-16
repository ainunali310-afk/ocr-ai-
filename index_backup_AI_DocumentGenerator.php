<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Document AI — Ubah Dokumen dengan AI</title>
    <meta name="description" content="Upload dokumen sumber dan template, biarkan AI menghasilkan dokumen baru sesuai format yang Anda inginkan.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="app-body">
    <!-- Navbar -->
    <nav class="navbar app-nav sticky-top">
        <div class="container py-2">
            <a class="nav-brand" href="index.php">
                <div class="nav-brand-icon"><i class="bi bi-file-earmark-richtext"></i></div>
                Smart Document AI
            </a>
            <div class="nav-actions">
                <a href="history.php" class="btn btn-outline-app btn-sm"><i class="bi bi-clock-history"></i> <span class="d-none d-md-inline">Riwayat</span></a>
                <?php if (isAdmin()): ?>
                <a href="manage.php" class="btn btn-outline-app btn-sm"><i class="bi bi-gear"></i></a>
                <?php endif; ?>
                <div class="d-none d-md-block text-end">
                    <div class="nav-user-name"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                    <div class="nav-user-role"><?php echo $currentUser['role']; ?></div>
                </div>
                <div class="dropdown">
                    <div class="nav-user" data-bs-toggle="dropdown"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div>
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
            <h3><i class="bi bi-camera-video" style="color:var(--accent)"></i> Ambil Foto Dokumen</h3>
            <video id="cameraVideo" autoplay playsinline></video>
            <canvas id="cameraCanvas" style="display:none"></canvas>
            <div id="cameraPreviewBox" style="display:none"><img id="capturedImage" style="width:100%;border-radius:12px;border:1px solid var(--border)"></div>
            <div class="camera-controls">
                <button type="button" class="btn btn-outline-app" onclick="switchCamera()"><i class="bi bi-arrow-repeat"></i> Putar</button>
                <button type="button" class="btn-shutter" id="captureBtn" onclick="capturePhoto()"><i class="bi bi-camera"></i></button>
                <button type="button" class="btn btn-outline-app" onclick="closeCamera()"><i class="bi bi-x-lg"></i> Tutup</button>
            </div>
            <div id="cameraActions" style="display:none;margin-top:14px;text-align:center">
                <button type="button" class="btn btn-primary-app" onclick="useCapturedPhoto()"><i class="bi bi-check-lg"></i> Gunakan Foto</button>
                <button type="button" class="btn btn-outline-app" onclick="retakePhoto()"><i class="bi bi-arrow-counterclockwise"></i> Ulang</button>
            </div>
        </div>
    </div>

    <main class="container py-4">
        <!-- Hero -->
        <section class="hero-section fade-in">
            <div class="hero-badge"><i class="bi bi-cpu"></i> Ditenagai AI Gemini</div>
            <h1 class="hero-title">Ubah Dokumen Lebih Cepat<br>dengan <span>AI</span></h1>
            <p class="hero-desc">Upload dokumen sumber dan template referensi, biarkan AI menghasilkan dokumen baru sesuai format yang Anda inginkan.</p>
        </section>

        <form action="upload.php" method="post" enctype="multipart/form-data" id="mainForm">
        <!-- 2-Input Row -->
        <div class="row g-4 mb-4">
            <!-- INPUT 1: Dokumen Sumber -->
            <div class="col-lg-6 fade-in fade-in-1">
                <div class="glass-card h-100">
                    <div class="section-head">
                        <div class="section-icon indigo"><i class="bi bi-file-earmark-text"></i></div>
                        <div>
                            <h2>📄 Dokumen Sumber</h2>
                            <p>Data utama yang ingin diproses</p>
                        </div>
                    </div>
                    <div class="upload-zone" id="sourceZone">
                        <div class="drop-icon" style="color:var(--primary-light)"><i class="bi bi-cloud-arrow-up"></i></div>
                        <div class="drop-title">Seret file ke sini</div>
                        <div class="drop-hint">JPG, PNG, PDF, DOCX, XLSX</div>
                        <input type="file" id="sourceFile" name="source_doc" accept="image/*,.pdf,.docx,.doc,.xlsx,.xls" required>
                        <div class="upload-btns">
                            <button type="button" class="btn btn-primary-app" onclick="document.getElementById('sourceFile').click()"><i class="bi bi-folder2-open"></i> Pilih File</button>
                            <button type="button" class="btn btn-outline-app" onclick="openCamera('source')"><i class="bi bi-camera"></i> Kamera</button>
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

            <!-- INPUT 2: Template Output -->
            <div class="col-lg-6 fade-in fade-in-2">
                <div class="glass-card h-100">
                    <div class="section-head">
                        <div class="section-icon cyan"><i class="bi bi-palette"></i></div>
                        <div>
                            <h2>🎨 Template Output</h2>
                            <p>Referensi tampilan & format hasil</p>
                        </div>
                    </div>
                    <div class="upload-zone" id="templateZone">
                        <div class="drop-icon" style="color:var(--accent)"><i class="bi bi-image"></i></div>
                        <div class="drop-title">Seret template ke sini</div>
                        <div class="drop-hint">JPG, PNG, PDF, DOCX, XLSX</div>
                        <input type="file" id="templateFile" name="template_doc" accept="image/*,.pdf,.docx,.doc,.xlsx,.xls">
                        <div class="upload-btns">
                            <button type="button" class="btn btn-primary-app" onclick="document.getElementById('templateFile').click()"><i class="bi bi-folder2-open"></i> Pilih File</button>
                            <button type="button" class="btn btn-outline-app" onclick="openCamera('template')"><i class="bi bi-camera"></i> Kamera</button>
                        </div>
                    </div>
                    <div class="file-preview" id="templatePreview">
                        <div class="file-icon" id="templateIcon"><i class="bi bi-file-earmark"></i></div>
                        <div class="file-info">
                            <div class="file-name" id="templateName">—</div>
                            <div class="file-meta" id="templateMeta">—</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Output Format -->
        <div class="glass-card mb-4 fade-in fade-in-3">
            <div class="section-head">
                <div class="section-icon violet"><i class="bi bi-filetype-pdf"></i></div>
                <div>
                    <h2>Pilihan Format Output</h2>
                    <p>Pilih format dokumen hasil yang diinginkan</p>
                </div>
            </div>
            <div class="format-grid">
                <div class="format-card selected" data-format="pdf" onclick="selectFormat(this)">
                    <div class="format-check"><i class="bi bi-check"></i></div>
                    <div class="format-icon" style="color:#EF4444"><i class="bi bi-file-earmark-pdf"></i></div>
                    <div class="format-name">PDF</div>
                    <div class="format-desc">Dokumen siap cetak</div>
                </div>
                <div class="format-card" data-format="word" onclick="selectFormat(this)">
                    <div class="format-check"><i class="bi bi-check"></i></div>
                    <div class="format-icon" style="color:#3B82F6"><i class="bi bi-file-earmark-word"></i></div>
                    <div class="format-name">Word (.docx)</div>
                    <div class="format-desc">Dokumen dapat diedit</div>
                </div>
                <div class="format-card" data-format="excel" onclick="selectFormat(this)">
                    <div class="format-check"><i class="bi bi-check"></i></div>
                    <div class="format-icon" style="color:#10B981"><i class="bi bi-file-earmark-spreadsheet"></i></div>
                    <div class="format-name">Excel (.xlsx)</div>
                    <div class="format-desc">Data berbentuk tabel</div>
                </div>
            </div>
            <input type="hidden" name="output_format" id="outputFormat" value="pdf">
        </div>

        <!-- AI Prompt -->
        <div class="glass-card mb-4 fade-in">
            <div class="section-head">
                <div class="section-icon green"><i class="bi bi-magic"></i></div>
                <div>
                    <h2>Sesuaikan Hasil dengan AI</h2>
                    <p>Tulis instruksi khusus untuk AI (opsional)</p>
                </div>
            </div>
            <textarea class="ai-prompt-area" name="ai_prompt" placeholder="Contoh:&#10;• Buat tabel daftar mahasiswa&#10;• Pisahkan nama dan nomor mahasiswa&#10;• Buat laporan resmi&#10;• Rapikan format surat"></textarea>
        </div>

        <!-- Generate Button -->
        <div class="text-center mb-5 fade-in">
            <button type="submit" class="btn btn-primary-app btn-lg" id="generateBtn" disabled>
                <i class="bi bi-cpu"></i> Generate Dokumen AI
            </button>
            <p style="color:var(--text-dim);font-size:0.82rem;margin-top:10px">Minimal upload dokumen sumber untuk memulai</p>
        </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script>
    // ========== DRAG & DROP ==========
    let activeTarget = 'source'; // for camera
    function setupDropZone(zoneId, inputId, previewId, iconId, nameId, metaId) {
        const zone = document.getElementById(zoneId);
        const input = document.getElementById(inputId);
        ['dragenter','dragover'].forEach(e => zone.addEventListener(e, ev => { ev.preventDefault(); zone.classList.add('drag-over'); }));
        ['dragleave','drop'].forEach(e => zone.addEventListener(e, ev => { ev.preventDefault(); zone.classList.remove('drag-over'); }));
        zone.addEventListener('drop', ev => {
            const f = ev.dataTransfer.files;
            if (f.length > 0) { input.files = f; showPreview(f[0], previewId, iconId, nameId, metaId, zoneId); }
        });
        input.addEventListener('change', () => { if (input.files.length > 0) showPreview(input.files[0], previewId, iconId, nameId, metaId, zoneId); });
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
    setupDropZone('templateZone','templateFile','templatePreview','templateIcon','templateName','templateMeta');

    // ========== FORMAT SELECT ==========
    function selectFormat(card) {
        document.querySelectorAll('.format-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        document.getElementById('outputFormat').value = card.dataset.format;
    }

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
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode, width:{ideal:1280}, height:{ideal:720} }, audio: false });
            document.getElementById('cameraVideo').srcObject = stream;
        } catch(e) { showToast('Tidak dapat mengakses kamera: '+e.message,'error'); closeCamera(); }
    }
    function closeCamera() {
        if (stream) { stream.getTracks().forEach(t=>t.stop()); stream=null; }
        document.getElementById('cameraVideo').srcObject = null;
        document.getElementById('cameraModal').classList.remove('active');
        document.getElementById('cameraPreviewBox').style.display='none';
        document.getElementById('cameraVideo').style.display='block';
        document.getElementById('cameraActions').style.display='none';
        document.getElementById('captureBtn').style.display='flex';
    }
    async function switchCamera() {
        facingMode = facingMode==='environment'?'user':'environment';
        if(stream){stream.getTracks().forEach(t=>t.stop());}
        try{stream=await navigator.mediaDevices.getUserMedia({video:{facingMode,width:{ideal:1280},height:{ideal:720}},audio:false});document.getElementById('cameraVideo').srcObject=stream;}catch(e){showToast('Gagal switch kamera','error');}
    }
    function capturePhoto() {
        const v=document.getElementById('cameraVideo'),c=document.getElementById('cameraCanvas');
        c.width=v.videoWidth;c.height=v.videoHeight;c.getContext('2d').drawImage(v,0,0);
        document.getElementById('capturedImage').src=c.toDataURL('image/jpeg',0.9);
        v.style.display='none';document.getElementById('cameraPreviewBox').style.display='block';
        document.getElementById('captureBtn').style.display='none';document.getElementById('cameraActions').style.display='block';
    }
    function retakePhoto(){document.getElementById('cameraPreviewBox').style.display='none';document.getElementById('cameraVideo').style.display='block';document.getElementById('captureBtn').style.display='flex';document.getElementById('cameraActions').style.display='none';}
    function useCapturedPhoto() {
        const c=document.getElementById('cameraCanvas');
        c.toBlob(blob=>{
            const file=new File([blob],'camera_'+Date.now()+'.jpg',{type:'image/jpeg'});
            const dt=new DataTransfer();dt.items.add(file);
            const inputId=activeTarget==='source'?'sourceFile':'templateFile';
            document.getElementById(inputId).files=dt.files;
            const ids=activeTarget==='source'?['sourcePreview','sourceIcon','sourceName','sourceMeta','sourceZone']:['templatePreview','templateIcon','templateName','templateMeta','templateZone'];
            showPreview(file,...ids);
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
    </script>
</body>
</html>
