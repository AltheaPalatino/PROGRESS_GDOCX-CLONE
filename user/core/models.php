<?php
// models.php

function getAllDocuments($pdo) {
    $stmt = $pdo->prepare("
        SELECT d.*, u.username 
        FROM documents d 
        JOIN users u ON d.owner_id = u.id 
        ORDER BY d.updated_at DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserDocuments($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT d.*, u.username 
        FROM documents d 
        JOIN users u ON d.owner_id = u.id 
        LEFT JOIN document_access da ON d.id = da.document_id 
        WHERE d.owner_id = ? OR da.user_id = ? 
        ORDER BY d.updated_at DESC
    ");
    $stmt->execute([$userId, $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDocumentById($pdo, $docId) {
    $stmt = $pdo->prepare("
        SELECT d.*, u.username 
        FROM documents d 
        JOIN users u ON d.owner_id = u.id 
        WHERE d.id = ?
    ");
    $stmt->execute([$docId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function userHasAccess($pdo, $userId, $docId) {
    $stmt = $pdo->prepare("
        SELECT * FROM document_access 
        WHERE document_id = ? AND user_id = ?
    ");
    $stmt->execute([$docId, $userId]);
    return $stmt->rowCount() > 0;
}

function getDocumentLogs($pdo, $docId) {
    $stmt = $pdo->prepare("
        SELECT a.*, u.username 
        FROM activity_logs a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.document_id = ? 
        ORDER BY a.timestamp DESC
    ");
    $stmt->execute([$docId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDocumentMessages($pdo, $docId) {
    $stmt = $pdo->prepare("
        SELECT m.*, u.username 
        FROM messages m 
        JOIN users u ON m.user_id = u.id 
        WHERE m.document_id = ? 
        ORDER BY m.timestamp ASC
    ");
    $stmt->execute([$docId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllUsers($pdo) {
    $stmt = $pdo->query("SELECT id, username, is_suspended FROM users ORDER BY username");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function isSuspended($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT is_suspended FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

function toggleSuspend($pdo, $userId, $suspend) {
    $stmt = $pdo->prepare("UPDATE users SET is_suspended = ? WHERE id = ?");
    $stmt->execute([$suspend ? 1 : 0, $userId]);
}
?>
