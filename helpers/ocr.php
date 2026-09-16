<?php
/**
 * OCR Helper - Tesseract Integration
 * Handles Optical Character Recognition using Tesseract
 */

/**
 * Run OCR on image file using Tesseract
 * 
 * @param string $imagePath Path to image file
 * @return string OCR extracted text, empty string if failed
 */
function runOcr($imagePath) {
    $candidates = [
        'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
        'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
        'tesseract'
    ];

    foreach ($candidates as $candidate) {
        if ($candidate === 'tesseract' && !isCommandAvailable('tesseract')) {
            continue;
        }

        if ($candidate !== 'tesseract' && !file_exists($candidate)) {
            continue;
        }

        $command = $candidate === 'tesseract'
            ? 'tesseract ' . escapeshellarg($imagePath) . ' stdout 2>&1'
            : '"' . $candidate . '" ' . escapeshellarg($imagePath) . ' stdout 2>&1';

        $output = shell_exec($command);
        if (!empty($output) && stripos($output, 'not recognized') === false) {
            return trim($output);
        }
    }

    return '';
}

/**
 * Check if command is available in system PATH
 * 
 * @param string $command Command name to check
 * @return bool True if command is available
 */
function isCommandAvailable($command) {
    $checkCommand = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
        ? 'where ' . escapeshellarg($command) . ' 2>NUL'
        : 'command -v ' . escapeshellarg($command) . ' 2>/dev/null';

    $output = shell_exec($checkCommand);
    return !empty($output);
}

/**
 * Extract approximate metadata from raw OCR text
 *
 * @param string $text OCR result text
 * @return array{judul:string|null,tahun:int|null,jenis_kegiatan:string|null}
 */
function extractMetadataFromText($text) {
    $text = trim($text);
    if ($text === '') {
        return [
            'judul' => null,
            'tahun' => null,
            'jenis_arsip' => null,
            'jenis_kegiatan' => null,
            'nomor_dokumen' => null,
            'nama_kegiatan' => null,
            'instansi' => null,
        ];
    }

    $lines = preg_split('/\r?\n+/', $text);
    $lines = array_filter(array_map('trim', $lines), fn($line) => $line !== '');
    $firstLine = $lines[0] ?? null;

    $year = null;
    if (preg_match('/\b(19\d{2}|20\d{2}|2100)\b/', $text, $yearMatch)) {
        $year = (int)$yearMatch[1];
        if ($year < 1900 || $year > (int)date('Y') + 1) {
            $year = null;
        }
    }

    $keywords = [
        'laporan' => 'Laporan',
        'proposal' => 'Proposal',
        'notulen' => 'Notulen',
        'surat' => 'Surat',
        'sertifikat' => 'Sertifikat',
        'undangan' => 'Undangan',
        'pengumuman' => 'Pengumuman',
        'berita acara' => 'Berita Acara',
        'permohonan' => 'Permohonan',
        'akta' => 'Akta',
        'kontrak' => 'Kontrak',
        'nota dinas' => 'Nota Dinas',
        'nota' => 'Nota',
        'brosur' => 'Brosur',
    ];

    $jenisArsip = null;
    foreach ($keywords as $keyword => $label) {
        if (stripos($text, $keyword) !== false) {
            $jenisArsip = $label;
            break;
        }
    }

    $jenisKegiatan = null;
    foreach (['kegiatan', 'acara', 'agenda', 'pelatihan', 'seminar'] as $keyword) {
        if (stripos($text, $keyword) !== false) {
            $jenisKegiatan = ucfirst($keyword);
            break;
        }
    }

    $nomorDokumen = null;
    if (preg_match('/Nomor\s*[:\-]?\s*([A-Za-z0-9\/.\-]+)\b/i', $text, $match)) {
        $nomorDokumen = trim($match[1]);
    }

    $namaKegiatan = null;
    if (preg_match('/(?:Nama Kegiatan|Kegiatan|Acara)\s*[:\-]?\s*(.+)/i', $text, $match)) {
        $namaKegiatan = trim($match[1]);
        if (strlen($namaKegiatan) > 180) {
            $namaKegiatan = substr($namaKegiatan, 0, 180);
        }
    }

    $instansi = null;
    if (preg_match('/(?:Instansi|Dinas|Kementerian|Lembaga|Sekolah|Universitas|PT\.|CV\.)\s*[:\-]?\s*(.+)/i', $text, $match)) {
        $instansi = trim($match[1]);
        if (strlen($instansi) > 180) {
            $instansi = substr($instansi, 0, 180);
        }
    }

    $judul = $firstLine;
    if ($judul !== null && strlen($judul) > 120) {
        $judul = substr($judul, 0, 120);
    }

    return [
        'judul' => $judul,
        'tahun' => $year,
        'jenis_arsip' => $jenisArsip,
        'jenis_kegiatan' => $jenisKegiatan,
        'nomor_dokumen' => $nomorDokumen,
        'nama_kegiatan' => $namaKegiatan,
        'instansi' => $instansi,
    ];
}

?>
