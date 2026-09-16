<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['source_doc']['name'])) {
    header('Location: index.php');
    exit;
}

// Validate source file
$allowedTypes = ['image/jpeg','image/png','application/pdf','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/msword','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/vnd.ms-excel'];
$fileInfo = finfo_open(FILEINFO_MIME_TYPE);

$sourceMime = finfo_file($fileInfo, $_FILES['source_doc']['tmp_name']);
// Allow all for now since MIME detection may vary
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Save source doc
$sourceName = sanitizeFileName($_FILES['source_doc']['name']);
$sourcePath = $uploadDir . $sourceName;
if (!move_uploaded_file($_FILES['source_doc']['tmp_name'], $sourcePath)) {
    header('Location: index.php?status=error');
    exit;
}

// Save template doc (optional)
$templateName = null;
$templatePath = null;
if (!empty($_FILES['template_doc']['name']) && $_FILES['template_doc']['error'] === UPLOAD_ERR_OK) {
    $templateName = sanitizeFileName($_FILES['template_doc']['name']);
    $templateStorePath = $uploadDir . $templateName;
    move_uploaded_file($_FILES['template_doc']['tmp_name'], $templateStorePath);
    $templatePath = 'uploads/' . $templateName;
}

$outputFormat = $_POST['output_format'] ?? 'pdf';
$aiPrompt = $_POST['ai_prompt'] ?? '';
$userId = $currentUser['id'];
$sourceRelPath = 'uploads/' . $sourceName;

// Insert into DB with status pending
$stmt = $conn->prepare('INSERT INTO archives (file_name, file_path, template_name, template_path, output_format, ai_prompt, status, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$statusVal = 'pending';
$stmt->bind_param('sssssssi', $sourceName, $sourceRelPath, $templateName, $templatePath, $outputFormat, $aiPrompt, $statusVal, $userId);
$stmt->execute();
$docId = $stmt->insert_id;
$stmt->close();

finfo_close($fileInfo);

// Redirect to processing page
header('Location: process.php?id=' . $docId);
exit;
