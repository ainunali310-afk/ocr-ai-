<?php
/**
 * Gemini Vision API Helper
 * Handles document analysis and data extraction using Google Gemini API
 */

/**
 * Extract structured archive metadata from an image using Gemini Vision API
 * 
 * @param string $imagePath Path to image file
 * @param string $apiKey Gemini API key
 * @return array|null Array with structured metadata or null on error
 */
function extractDataWithGemini($imagePath, $apiKey, $additionalInstructions = '') {
    if (empty($apiKey) || !extension_loaded('curl')) {
        return null;
    }

    $imageData = file_get_contents($imagePath);
    if ($imageData === false) {
        error_log("Failed to read image: $imagePath");
        return null;
    }

    $base64Image = base64_encode($imageData);
    $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';

    $mainPrompt = 'Baca gambar dokumen arsip ini dan ekstrak semua metadata penting. Balas HANYA JSON valid tanpa markdown. Gunakan field: raw_text, judul, tahun, nomor_dokumen, jenis_arsip, jenis_kegiatan, nama_kegiatan, instansi, ringkasan, data_terstruktur. Jika nilai tidak ditemukan, gunakan null. Contoh: {"raw_text":"...","judul":"...","tahun":2024,"nomor_dokumen":"...","jenis_arsip":"...","jenis_kegiatan":"...","nama_kegiatan":"...","instansi":"...","ringkasan":"...","data_terstruktur":{"key":"value"}}';
    if (!empty($additionalInstructions)) {
        $mainPrompt .= ' Instruksi tambahan: ' . trim($additionalInstructions);
    }

    $payload = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $mainPrompt
                    ],
                    [
                        'inline_data' => [
                            'mime_type' => $mimeType,
                            'data' => $base64Image
                        ]
                    ]
                ]
            ]
        ]
    ];

    $models = defined('GEMINI_MODELS') ? GEMINI_MODELS : ['gemini-2.0-flash'];
    $response = null;

    foreach ($models as $model) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . $apiKey;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, defined('API_TIMEOUT') ? API_TIMEOUT : 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!empty($curlError)) {
            error_log("cURL Error ($model): $curlError");
            continue;
        }

        if ($httpCode === 200) {
            break;
        }

        error_log("API Error ($model, HTTP $httpCode): $response");
        $response = null;
    }

    if ($response === null) {
        return null;
    }

    $responseData = json_decode($response, true);
    if (!$responseData) {
        error_log("Failed to decode API response: $response");
        return null;
    }

    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        $text = $responseData['candidates'][0]['content']['parts'][0]['text'];
        $text = trim(str_replace(['```json', '```'], '', $text));
        
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $jsonData = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return [
                    'raw_text' => $jsonData['raw_text'] ?? null,
                    'judul' => $jsonData['judul'] ?? null,
                    'tahun' => isset($jsonData['tahun']) && $jsonData['tahun'] !== '' ? (int)$jsonData['tahun'] : null,
                    'nomor_dokumen' => $jsonData['nomor_dokumen'] ?? null,
                    'jenis_arsip' => $jsonData['jenis_arsip'] ?? null,
                    'jenis_kegiatan' => $jsonData['jenis_kegiatan'] ?? null,
                    'nama_kegiatan' => $jsonData['nama_kegiatan'] ?? null,
                    'instansi' => $jsonData['instansi'] ?? null,
                    'ringkasan' => $jsonData['ringkasan'] ?? null,
                    'data_terstruktur' => $jsonData['data_terstruktur'] ?? null,
                ];
            }
        }
    }

    error_log("Failed to extract structured data from API response");
    return null;
}

?>
