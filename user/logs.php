<?php
require_once '../core/dbConfig.php';

$docId = $_GET['id'];

$stmt = $pdo->prepare("
    SELECT a.*, u.username FROM activity_logs a
    JOIN users u ON a.user_id = u.id
    WHERE a.document_id = ?
    ORDER BY a.timestamp DESC
");
$stmt->execute([$docId]);
$logs = $stmt->fetchAll();
?>

<h3>Activity Logs</h3>
<ul>
    <?php foreach ($logs as $log): ?>
        <li><?= $log['timestamp'] ?> - <?= htmlspecialchars($log['username']) ?>: <?= htmlspecialchars($log['action']) ?></li>
    <?php endforeach; ?>
</ul>
