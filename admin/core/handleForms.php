<?php
require_once 'dbConfig.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_suspend') {
        $userId = $_POST['user_id'];
        $status = $_POST['status'] ?? 0;
        $stmt = $pdo->prepare("UPDATE users SET is_suspended = ? WHERE id = ?");
        $stmt->execute([$status, $userId]);
        exit;
    }

    if ($action === 'create_document') {
        $title = $_POST['title'];
        $ownerId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("INSERT INTO documents (title, owner_id, content) VALUES (?, ?, '')");
        $stmt->execute([$title, $ownerId]);
        header("Location: ../index.php");
        exit;
    }

    if ($action === 'admin_save_doc') {
        $docId = $_POST['doc_id'];
        $content = $_POST['content'];
        $stmt = $pdo->prepare("UPDATE documents SET content = ? WHERE id = ?");
        $stmt->execute([$content, $docId]);

        $log = $pdo->prepare("INSERT INTO activity_logs (document_id, user_id, action) VALUES (?, ?, ?)");
        $log->execute([$docId, $_SESSION['user_id'], 'Admin updated document manually']);
        header("Location: ../document.php?id=$docId");
        exit;
    }

    if ($action === 'autosave') {
        $docId = $_POST['doc_id'];
        $content = $_POST['content'];

        $stmt = $pdo->prepare("UPDATE documents SET content = ? WHERE id = ?");
        $stmt->execute([$content, $docId]);

        $log = $pdo->prepare("INSERT INTO activity_logs (document_id, user_id, action) VALUES (?, ?, ?)");
        $log->execute([$docId, $_SESSION['user_id'], 'Auto-saved document']);
        exit;
    }
}
?>
