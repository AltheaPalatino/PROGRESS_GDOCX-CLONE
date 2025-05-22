<?php
require_once 'dbConfig.php';

if (!isset($_SESSION['user_id'])) {
    die("Access denied. Not logged in.");
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// ✅ CREATE DOCUMENT
if ($action === 'create_document') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';

    if (empty($title)) {
        die("Title is required.");
    }

    // Optional: Check user exists (can be removed if sessions are reliable)
    $check = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $check->execute([$userId]);
    if (!$check->fetch()) {
        die("Invalid user.");
    }

    $stmt = $pdo->prepare("INSERT INTO documents (title, content, owner_id) VALUES (?, ?, ?)");
    $stmt->execute([$title, $content, $userId]);

    header("Location: ../index.php");
    exit;
}

// ✅ AUTO-SAVE CONTENT
if ($action === 'autosave') {
    $docId = $_POST['doc_id'] ?? '';
    $content = $_POST['content'] ?? '';

    if (!empty($docId)) {
        $stmt = $pdo->prepare("UPDATE documents SET content = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$content, $docId]);

        // Log activity
        $log = $pdo->prepare("INSERT INTO activity_logs (document_id, user_id, action) VALUES (?, ?, ?)");
        $log->execute([$docId, $userId, "Auto-saved content"]);

        echo "Saved";
    }
    exit;
}
