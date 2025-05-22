<?php
require_once 'core/dbConfig.php';

$docId = $_GET['id'];
$userId = $_SESSION['user_id'];

// Check if admin has access or ownership
$accessCheck = $pdo->prepare("
    SELECT d.*, u.username AS owner FROM documents d
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
    <p>Owner: <?= htmlspecialchars($doc['owner']) ?></p>

    <?php if ($canEdit): ?>
        <form method="POST" action="core/handleForms.php">
            <textarea name="content" rows="10" cols="80"><?= htmlspecialchars($doc['content']) ?></textarea>
            <input type="hidden" name="action" value="admin_save_doc">
            <input type="hidden" name="doc_id" value="<?= $docId ?>">
            <button type="submit">Save</button>
        </form>
    <?php else: ?>
        <div style="border:1px solid #ccc; padding:10px;">
            <?= nl2br(htmlspecialchars($doc['content'])) ?>
        </div>
        <p><i>(You don't have permission to edit this document)</i></p>
    <?php endif; ?>
</body>
</html>
