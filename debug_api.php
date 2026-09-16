<?php
require_once 'config.php';

echo "<h1>Debug Gemini Vision API</h1>";

// Test 1: Check if cURL is installed
echo "<h2>1. cURL Status</h2>";
if (extension_loaded('curl')) {
    echo "<p style='color: green;'>✓ cURL is installed</p>";
} else {
    echo "<p style='color: red;'>✗ cURL is NOT installed</p>";
    die("cURL extension is required!");
}

// Test 2: Check API Key
echo "<h2>2. API Key Status</h2>";
if (!empty($geminiApiKey)) {
    echo "<p style='color: green;'>✓ API Key is configured</p>";
    echo "<p>Key: " . substr($geminiApiKey, 0, 20) . "...</p>";
} else {
    echo "<p style='color: red;'>✗ API Key is NOT configured</p>";
}

// Test 3: Check uploads directory
echo "<h2>3. Uploads Directory</h2>";
if (is_dir($uploadDir)) {
    echo "<p style='color: green;'>✓ Uploads directory exists: $uploadDir</p>";
} else {
    echo "<p style='color: red;'>✗ Uploads directory does NOT exist</p>";
}

// Test 4: Check database connection
echo "<h2>4. Database Connection</h2>";
if ($conn->connect_error) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✓ Database connected</p>";
    
    // Check if archives table exists
    $result = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema='$dbName' AND table_name='archives'");
    if ($result && $result->fetch_assoc()['cnt'] > 0) {
        echo "<p style='color: green;'>✓ Archives table exists</p>";
        
        // Check columns
        $columns = $conn->query("DESCRIBE archives");
        echo "<p>Table columns:</p><ul>";
        while ($col = $columns->fetch_assoc()) {
            echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Archives table does NOT exist</p>";
    }
}

// Test 5: Test API with a simple request
echo "<h2>5. Test Gemini API Connection</h2>";

$testPayload = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => 'Return this JSON: {"judul": "test", "tahun": 2024, "jenis_kegiatan": "test"}'
                ]
            ]
        ]
    ]
];

$model = $geminiModels[0] ?? 'gemini-2.0-flash';
$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . $geminiApiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if (!empty($curlError)) {
    echo "<p style='color: red;'>✗ cURL Error: $curlError</p>";
} else if ($httpCode === 200) {
    echo "<p style='color: green;'>✓ API Connection Successful (HTTP 200)</p>";
    $responseData = json_decode($response, true);
    echo "<p>Response structure:</p>";
    echo "<pre>" . json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
} else {
    echo "<p style='color: red;'>✗ API Error (HTTP $httpCode)</p>";
    echo "<p>Response:</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

echo "<h2>6. Test File Upload</h2>";
echo "<p>Upload a test image to verify the full flow works.</p>";
echo "<p><a href='index.php'>Go to Dashboard</a></p>";

?>
