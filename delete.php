<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();

$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
} else {
    $id = (int)($_GET['id'] ?? 0);
}

if ($id > 0) {
    $stmt = $conn->prepare('SELECT file_path, user_id FROM archives WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $archive = $result->fetch_assoc();
    $stmt->close();

    if ($archive) {
        // Only Admin can delete
        if ($currentUser['role'] === 'admin') {
            $filePath = __DIR__ . '/' . ltrim($archive['file_path'], '/');
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Also delete template file if exists
            $tplStmt = $conn->prepare('SELECT template_path FROM archives WHERE id = ?');
            $tplStmt->bind_param('i', $id);
            $tplStmt->execute();
            $tplDoc = $tplStmt->get_result()->fetch_assoc();
            $tplStmt->close();
            if (!empty($tplDoc['template_path'])) {
                $tplFile = __DIR__ . '/' . ltrim($tplDoc['template_path'], '/');
                if (file_exists($tplFile)) unlink($tplFile);
            }

            $deleteStmt = $conn->prepare('DELETE FROM archives WHERE id = ?');
            $deleteStmt->bind_param('i', $id);
            $deleteStmt->execute();
            $deleteStmt->close();
        }
    }
}

header('Location: history.php');
exit;
