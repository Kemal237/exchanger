<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

$upload_dir = __DIR__ . '/../uploads/admin-notes/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// GET: load notes for entity
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $entity_type = $_GET['entity_type'] ?? '';
    $entity_id   = trim($_GET['entity_id'] ?? '');

    if (!in_array($entity_type, ['user', 'order']) || $entity_id === '') {
        echo json_encode(['error' => 'Invalid params']); exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM admin_notes WHERE entity_type = ? AND entity_id = ? ORDER BY created_at DESC");
    $stmt->execute([$entity_type, $entity_id]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($notes as &$note) {
        $fs = $pdo->prepare("SELECT id, filename, original_name FROM admin_note_files WHERE note_id = ?");
        $fs->execute([$note['id']]);
        $note['files'] = $fs->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($notes);
    exit;
}

// POST: add or delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $note_id = (int)($_POST['note_id'] ?? 0);
        $fs = $pdo->prepare("SELECT filename FROM admin_note_files WHERE note_id = ?");
        $fs->execute([$note_id]);
        foreach ($fs->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $p = $upload_dir . $f['filename'];
            if (file_exists($p)) unlink($p);
        }
        $pdo->prepare("DELETE FROM admin_notes WHERE id = ?")->execute([$note_id]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'add') {
        $entity_type = $_POST['entity_type'] ?? '';
        $entity_id   = trim($_POST['entity_id'] ?? '');
        $note_text   = trim($_POST['note_text'] ?? '');
        $admin_name  = $_SESSION['username'] ?? 'Администратор';

        if (!in_array($entity_type, ['user', 'order']) || $entity_id === '') {
            echo json_encode(['error' => 'Invalid params']); exit;
        }

        $has_text  = $note_text !== '';
        $has_files = !empty($_FILES['files']['name'][0]);

        if (!$has_text && !$has_files) {
            echo json_encode(['error' => 'Добавьте текст или прикрепите файл']); exit;
        }

        $stmt = $pdo->prepare("INSERT INTO admin_notes (entity_type, entity_id, admin_name, note_text) VALUES (?, ?, ?, ?)");
        $stmt->execute([$entity_type, $entity_id, $admin_name, $note_text]);
        $note_id = (int)$pdo->lastInsertId();

        $uploaded = [];
        if ($has_files) {
            foreach ($_FILES['files']['name'] as $i => $orig) {
                if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK || !$orig) continue;
                $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                $fn  = bin2hex(random_bytes(16)) . ($ext ? '.' . $ext : '');
                if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $upload_dir . $fn)) {
                    $pdo->prepare("INSERT INTO admin_note_files (note_id, filename, original_name) VALUES (?, ?, ?)")
                        ->execute([$note_id, $fn, $orig]);
                    $fid = (int)$pdo->lastInsertId();
                    $uploaded[] = ['id' => $fid, 'filename' => $fn, 'original_name' => $orig];
                }
            }
        }

        echo json_encode([
            'success' => true,
            'note'    => [
                'id'         => $note_id,
                'note_text'  => $note_text,
                'created_at' => date('Y-m-d H:i:s'),
                'admin_name' => $admin_name,
                'files'      => $uploaded,
            ],
        ]);
        exit;
    }
}

echo json_encode(['error' => 'Unknown action']);
