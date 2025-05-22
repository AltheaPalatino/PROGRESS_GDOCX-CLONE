<?php
require_once '../core/dbConfig.php';

$docId = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = $_POST['message'];
    $stmt = $pdo->prepare("INSERT INTO messages (document_id, user_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$docId, $_SESSION['user_id'], $msg]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT m.*, u.username FROM messages m
    JOIN users u ON m.user_id = u.id
    WHERE m.document_id = ?
    ORDER BY m.timestamp ASC
");
$stmt->execute([$docId]);
$messages = $stmt->fetchAll();
?>

<h3>Chat</h3>
<div id="chatBox" style="border:1px solid #aaa; height:200px; overflow:auto; padding:5px;">
    <?php foreach ($messages as $msg): ?>
        <div><strong><?= htmlspecialchars($msg['username']) ?>:</strong> <?= htmlspecialchars($msg['message']) ?> <small>(<?= $msg['timestamp'] ?>)</small></div>
    <?php endforeach; ?>
</div>

<form method="post" onsubmit="sendMessage(); return false;">
    <input type="text" id="chatMsg" required>
    <button type="submit">Send</button>
</form>

<script>
function sendMessage() {
    const msg = document.getElementById('chatMsg').value;
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "messages.php?id=<?= $docId ?>");
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = () => location.reload();
    xhr.send("message=" + encodeURIComponent(msg));
}
</script>
