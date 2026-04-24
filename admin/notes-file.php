<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403); exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$file_id = (int)($_GET['id'] ?? 0);
if (!$file_id) { http_response_code(404); exit; }

$stmt = $pdo->prepare("SELECT filename, original_name FROM admin_note_files WHERE id = ?");
$stmt->execute([$file_id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$file) { http_response_code(404); exit; }

$path = __DIR__ . '/../uploads/admin-notes/' . $file['filename'];
if (!file_exists($path)) { http_response_code(404); exit; }

$mime = mime_content_type($path) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . rawurlencode($file['original_name']) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
