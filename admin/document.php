<?php
require_once 'core/dbConfig.php';

if (!isset($_SESSION['user_id'])) {
    die("Access denied.");
}

$docId = $_GET['id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$docId) {
    die("Document ID is missing.");
}

// Check if admin has access or ownership
$accessCheck = $pdo->prepare("
    SELECT d.*, u.name AS owner_name 
    FROM documents d
    JOIN users u ON d.owner_id = u.id
    WHERE d.id = ?
");
$accessCheck->execute([$docId]);
$doc = $accessCheck->fetch();

if (!$doc) {
    die("Document not found.");
}

// Check if admin has access to edit
$canEdit = false;
$access = $pdo->prepare("SELECT * FROM document_access WHERE document_id = ? AND user_id = ?");
$access->execute([$docId, $userId]);
if ($access->rowCount()) {
    $canEdit = true;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($doc['title']) ?> (Admin)</title>
</head>
<body>
    <h1><?= htmlspecialchars($doc['title']) ?></h1>
    <p>Owner: <?= htmlspecialchars($doc['owner_name']) ?></p>

    <?php if ($canEdit): ?>
        <form method="POST" action="../core/handleForms.php">
            <textarea name="content" rows="10" cols="80"><?= htmlspecialchars($doc['content']) ?></textarea>
            <input type="hidden" name="action" value="admin_save_doc">
            <input type="hidden" name="doc_id" value="<?= $docId ?>">
            <button type="submit">Save</button>
        </form>
    <?php else: ?>
        <div style="border:1px solid #ccc; padding:10px;">
            <?= nl2br(htmlspecialchars($doc['content'])) ?>
        </div>
        <p><i>(You do not have permission to edit this document)</i></p>
    <?php endif; ?>
</body>
</html>
