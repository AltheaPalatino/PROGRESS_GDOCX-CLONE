<?php
require_once '../core/dbConfig.php';

$userId = $_POST['user_id'];
$docId = $_POST['doc_id'];

$stmt = $pdo->prepare("INSERT IGNORE INTO document_access (document_id, user_id) VALUES (?, ?)");
$stmt->execute([$docId, $userId]);

$log = $pdo->prepare("INSERT INTO activity_logs (document_id, user_id, action) VALUES (?, ?, ?)");
$log->execute([$docId, $_SESSION['user_id'], "Added user $userId to document"]);
?>
