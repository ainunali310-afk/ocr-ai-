<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();

$format = strtolower($_POST['format'] ?? $_GET['format'] ?? '');
$selectedIds = $_POST['ids'] ?? [];
$exportTahun = $_POST['export_tahun'] ?? $_GET['tahun'] ?? '';
$searchJudul = $_POST['search_judul'] ?? $_GET['judul'] ?? '';
$includePhoto = ($_POST['include_photo'] ?? $_GET['include_photo'] ?? '') === '1';
$mode = $_POST['mode'] ?? $_GET['mode'] ?? 'full';
$tampilan = $_POST['tampilan'] ?? $_GET['tampilan'] ?? 'table';

if (!in_array($format, ['excel', 'word', 'pdf'], true)) {
    http_response_code(400);
    echo 'Format ekspor tidak valid.';
    exit;
}

$archives = getExportArchives($conn, $selectedIds, $exportTahun, $searchJudul);

if (empty($archives)) {
    http_response_code(404);
    echo 'Tidak ada dokumen yang bisa diekspor.';
    exit;
}

$fileSuffix = date('Ymd_His');

if ($mode === 'simple') {
    if ($tampilan === 'table') {
        exportSimpleTable($archives, "arsip_simple_table_$fileSuffix." . ($format === 'excel' ? 'xls' : 'pdf'), $format, $exportTahun);
    } else {
        exportSimpleList($archives, "arsip_simple_list_$fileSuffix." . ($format === 'word' ? 'doc' : 'pdf'), $format, $exportTahun);
    }
    exit;
}

if ($format === 'excel') {
    exportExcel($archives, "arsip_digital_$fileSuffix.xls", $includePhoto);
}

if ($format === 'word') {
    exportWord($archives, "arsip_digital_$fileSuffix.doc", $includePhoto);
}

exportPdf($archives, "arsip_digital_$fileSuffix.pdf", $includePhoto);

function getExportArchives($conn, $selectedIds, $exportTahun, $searchJudul = '')
{
    $ids = array_values(array_filter(array_map('intval', (array)$selectedIds), fn($id) => $id > 0));

    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $stmt = $conn->prepare("SELECT * FROM archives WHERE id IN ($placeholders) ORDER BY uploaded_at DESC");
        $stmt->bind_param($types, ...$ids);
        return fetchRows($stmt);
    }

    $conditions = [];
    $params = [];
    $types = '';

    if ($exportTahun !== '') {
        $conditions[] = 'tahun = ?';
        $params[] = (int)$exportTahun;
        $types .= 'i';
    }

    if ($searchJudul !== '') {
        $conditions[] = 'judul LIKE ?';
        $params[] = "%$searchJudul%";
        $types .= 's';
    }

    if (!empty($conditions)) {
        $where = implode(' AND ', $conditions);
        $stmt = $conn->prepare("SELECT * FROM archives WHERE $where ORDER BY uploaded_at DESC");
        $stmt->bind_param($types, ...$params);
        return fetchRows($stmt);
    }

    $result = $conn->query('SELECT * FROM archives ORDER BY uploaded_at DESC');
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function fetchRows($stmt)
{
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function exportSimpleTable($archives, $fileName, $format, $exportTahun = '')
{
    $showYear = ($exportTahun === '');
    if ($format === 'excel') {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        echo "\xEF\xBB\xBF";
        echo '<table border="1">';
        echo '<tr><th>No</th>' . ($showYear ? '<th>Tahun</th>' : '') . '<th>Judul</th><th>Perihal</th></tr>';
        foreach ($archives as $i => $a) {
            echo '<tr>';
            echo '<td>' . ($i + 1) . '</td>';
            if ($showYear) echo '<td>' . e($a['tahun'] ?? '-') . '</td>';
            echo '<td>' . e($a['judul'] ?: $a['file_name']) . '</td>';
            echo '<td>' . e($a['nama_kegiatan'] ?: $a['jenis_kegiatan'] ?: '-') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        exit;
    }

    $pages = [];
    $lines = ['DAFTAR ARSIP (TABLE)', 'Tanggal: ' . date('d-m-Y H:i'), ''];
    $header = str_pad('No', 4) . ' | ' . ($showYear ? str_pad('Tahun', 6) . ' | ' : '') . str_pad('Judul', 50) . ' | ' . 'Perihal';
    $lines[] = $header;
    $lines[] = str_repeat('-', strlen($header));
    foreach ($archives as $i => $a) {
        $no = ($i + 1) . '.';
        $judul = normalizeText($a['judul'] ?: $a['file_name']);
        $perihal = normalizeText($a['nama_kegiatan'] ?: $a['jenis_kegiatan'] ?: '-');
        $row = str_pad($no, 4) . ' | ';
        if ($showYear) $row .= str_pad(substr($a['tahun'] ?? '-', 0, 6), 6) . ' | ';
        $row .= str_pad(substr($judul, 0, 48), 50) . ' | ' . substr($perihal, 0, 30);
        $lines[] = $row;
    }
    $pages[] = ['lines' => $lines, 'image' => null];
    $pdf = buildSimplePdf($pages);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}

function exportSimpleList($archives, $fileName, $format, $exportTahun = '')
{
    $showYear = ($exportTahun === '');
    if ($format === 'word') {
        header('Content-Type: application/msword; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        echo "\xEF\xBB\xBF";
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        echo '<style>body{font-family:Arial,sans-serif;color:#111827}h1{color:#120136}.item{border-bottom:1px solid #ddd;padding:12px 0}.judul{font-size:1.05rem;font-weight:700}.perihal{color:#555;margin-top:4px}</style>';
        echo '</head><body>';
        echo '<h1>Daftar Arsip (List)</h1>';
        foreach ($archives as $i => $a) {
            echo '<div class="item">';
            echo '<div class="judul">' . ($i + 1) . '. ' . e($a['judul'] ?: $a['file_name']) . '</div>';
            if ($showYear) echo '<div class="perihal">Tahun: ' . e($a['tahun'] ?? '-') . '</div>';
            echo '<div class="perihal">Perihal: ' . e($a['nama_kegiatan'] ?: $a['jenis_kegiatan'] ?: '-') . '</div>';
            echo '</div>';
        }
        echo '</body></html>';
        exit;
    }

    $pages = [];
    $lines = ['DAFTAR ARSIP (LIST)', 'Tanggal: ' . date('d-m-Y H:i'), ''];
    foreach ($archives as $i => $a) {
        $lines[] = ($i + 1) . '. ' . normalizeText($a['judul'] ?: $a['file_name']);
        if ($showYear) $lines[] = '   Tahun: ' . ($a['tahun'] ?? '-');
        $lines[] = '   Perihal: ' . normalizeText($a['nama_kegiatan'] ?: $a['jenis_kegiatan'] ?: '-');
        $lines[] = '';
    }
    $pages[] = ['lines' => $lines, 'image' => null];
    $pdf = buildSimplePdf($pages);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}

function exportExcel($archives, $fileName, $includePhoto)
{
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    echo "\xEF\xBB\xBF";
    echo '<table border="1">';
    echo '<tr><th>No</th>';
    if ($includePhoto) {
        echo '<th>Foto Dokumen</th>';
    }
    echo '<th>Judul</th><th>Tahun</th><th>Jenis Kegiatan</th><th>Nama File</th><th>Waktu Upload</th><th>Hasil OCR</th></tr>';
    foreach ($archives as $index => $archive) {
        echo '<tr>';
        echo '<td>' . ($index + 1) . '</td>';
        if ($includePhoto) {
            echo '<td>' . renderImageTag($archive, 180) . '</td>';
        }
        echo '<td>' . e($archive['judul'] ?: $archive['file_name']) . '</td>';
        echo '<td>' . e($archive['tahun'] ?? '-') . '</td>';
        echo '<td>' . e($archive['jenis_kegiatan'] ?? '-') . '</td>';
        echo '<td>' . e($archive['file_name']) . '</td>';
        echo '<td>' . e($archive['uploaded_at']) . '</td>';
        echo '<td>' . nl2br(e($archive['ocr_text'] ?? '')) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit;
}

function exportWord($archives, $fileName, $includePhoto)
{
    header('Content-Type: application/msword; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    echo "\xEF\xBB\xBF";
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
    echo '<style>body{font-family:Arial,sans-serif;color:#111827}h1{color:#120136}.doc{border-bottom:1px solid #ddd;padding:16px 0}.meta{color:#555}.photo{max-width:420px;height:auto;border:1px solid #ddd;margin:8px 0}</style>';
    echo '</head><body>';
    echo '<h1>Laporan Arsip Digital</h1>';
    foreach ($archives as $index => $archive) {
        echo '<div class="doc">';
        echo '<h2>' . ($index + 1) . '. ' . e($archive['judul'] ?: $archive['file_name']) . '</h2>';
        echo '<p class="meta"><strong>Tahun:</strong> ' . e($archive['tahun'] ?? '-') . ' | <strong>Jenis:</strong> ' . e($archive['jenis_kegiatan'] ?? '-') . ' | <strong>Upload:</strong> ' . e($archive['uploaded_at']) . '</p>';
        echo '<p><strong>Nama File:</strong> ' . e($archive['file_name']) . '</p>';
        if ($includePhoto) {
            echo '<p><strong>Foto Dokumen:</strong></p>';
            echo renderImageTag($archive, 420, 'photo');
        }
        echo '<p><strong>Hasil OCR:</strong></p>';
        echo '<p>' . nl2br(e($archive['ocr_text'] ?? '')) . '</p>';
        echo '</div>';
    }
    echo '</body></html>';
    exit;
}

function exportPdf($archives, $fileName, $includePhoto)
{
    $pages = [];
    $currentLines = ['LAPORAN ARSIP DIGITAL', 'Tanggal ekspor: ' . date('d-m-Y H:i'), ''];
    foreach ($archives as $index => $archive) {
        $lines = [];
        $lines[] = ($index + 1) . '. ' . normalizeText($archive['judul'] ?: $archive['file_name']);
        $lines[] = 'Tahun: ' . ($archive['tahun'] ?? '-') . ' | Jenis: ' . ($archive['jenis_kegiatan'] ?? '-');
        $lines[] = 'Nama File: ' . normalizeText($archive['file_name']);
        $lines[] = 'Upload: ' . ($archive['uploaded_at'] ?? '-');
        $lines[] = 'OCR:';
        foreach (wrapPdfText(normalizeText($archive['ocr_text'] ?? ''), 92) as $wrappedLine) {
            $lines[] = $wrappedLine;
        }
        $lines[] = '';

        foreach (array_chunk($lines, 42) as $chunk) {
            if (count($currentLines) + count($chunk) > 48) {
                $pages[] = ['lines' => $currentLines, 'image' => null];
                $currentLines = [];
            }
            $currentLines = array_merge($currentLines, $chunk);
        }

        if ($includePhoto) {
            $pdfImage = getPdfImage($archive);
            if ($pdfImage) {
                if (!empty($currentLines)) {
                    $pages[] = ['lines' => $currentLines, 'image' => null];
                    $currentLines = [];
                }
                $pages[] = [
                    'lines' => ['Foto Dokumen: ' . normalizeText($archive['file_name'])],
                    'image' => $pdfImage
                ];
            }
        }
    }

    if (!empty($currentLines)) {
        $pages[] = ['lines' => $currentLines, 'image' => null];
    }

    $pdf = buildSimplePdf($pages);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}

function buildSimplePdf($pages)
{
    $objects = [];
    $pageIds = [];

    $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
    $objects[] = '';
    $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

    foreach ($pages as $page) {
        $imageObjectId = null;
        if (!empty($page['image'])) {
            $imageObjectId = count($objects) + 1;
            $image = $page['image'];
            $objects[] = "<< /Type /XObject /Subtype /Image /Width {$image['width']} /Height {$image['height']} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($image['data']) . " >>\nstream\n{$image['data']}\nendstream";
        }

        $contentId = count($objects) + 2;
        $pageId = count($objects) + 1;
        $pageIds[] = $pageId . ' 0 R';

        $xObject = $imageObjectId ? " /XObject << /Im1 $imageObjectId 0 R >>" : '';
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >>$xObject >> /Contents $contentId 0 R >>";

        $streamLines = ['BT', '/F1 10 Tf', '40 800 Td', '14 TL'];
        foreach ($page['lines'] as $line) {
            $streamLines[] = '(' . escapePdfText($line) . ') Tj';
            $streamLines[] = 'T*';
        }
        $streamLines[] = 'ET';

        if (!empty($page['image'])) {
            $box = fitImageBox($page['image']['width'], $page['image']['height'], 500, 560);
            $streamLines[] = 'q';
            $streamLines[] = sprintf('%.2F 0 0 %.2F %.2F %.2F cm', $box['width'], $box['height'], 47.5, 200);
            $streamLines[] = '/Im1 Do';
            $streamLines[] = 'Q';
        }

        $stream = implode("\n", $streamLines);
        $objects[] = "<< /Length " . strlen($stream) . " >>\nstream\n$stream\nendstream";
    }

    $objects[1] = '<< /Type /Pages /Kids [' . implode(' ', $pageIds) . '] /Count ' . count($pageIds) . ' >>';

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($index + 1) . " 0 obj\n$object\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xrefOffset\n%%EOF";
    return $pdf;
}

function renderImageTag($archive, $width, $class = '')
{
    $path = __DIR__ . '/' . ltrim($archive['file_path'] ?? '', '/');
    if (!is_file($path)) {
        return '<span>Foto tidak ditemukan</span>';
    }

    $mimeType = mime_content_type($path);
    if (!in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
        return '<span>Format foto tidak didukung</span>';
    }

    $data = base64_encode(file_get_contents($path));
    $classAttr = $class !== '' ? ' class="' . e($class) . '"' : '';
    return '<img' . $classAttr . ' src="data:' . e($mimeType) . ';base64,' . $data . '" width="' . (int)$width . '" alt="' . e($archive['file_name'] ?? 'Foto dokumen') . '">';
}

function getPdfImage($archive)
{
    $path = __DIR__ . '/' . ltrim($archive['file_path'] ?? '', '/');
    if (!is_file($path)) {
        return null;
    }

    $info = @getimagesize($path);
    if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/jpg'], true)) {
        return null;
    }

    return [
        'width' => (int)$info[0],
        'height' => (int)$info[1],
        'data' => file_get_contents($path)
    ];
}

function fitImageBox($imageWidth, $imageHeight, $maxWidth, $maxHeight)
{
    $ratio = min($maxWidth / max(1, $imageWidth), $maxHeight / max(1, $imageHeight));
    return [
        'width' => $imageWidth * $ratio,
        'height' => $imageHeight * $ratio
    ];
}

function wrapPdfText($text, $width)
{
    $lines = [];
    foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
        $wrapped = wordwrap($line, $width, "\n", true);
        foreach (explode("\n", $wrapped) as $wrappedLine) {
            $lines[] = $wrappedLine;
        }
    }
    return $lines;
}

function escapePdfText($text)
{
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function normalizeText($text)
{
    return trim(preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', (string)$text));
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
