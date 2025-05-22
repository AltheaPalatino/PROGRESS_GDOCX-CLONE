<?php
require_once 'core/dbConfig.php';

// Simulate login (replace with actual session handling later)
$_SESSION['user_id'] = 2; // user1
$_SESSION['role'] = 'user';

$userId = $_SESSION['user_id'];

// Fetch owned and shared documents
$ownedDocs = $pdo->prepare("SELECT * FROM documents WHERE owner_id = ?");
$ownedDocs->execute([$userId]);

$sharedDocs = $pdo->prepare("
    SELECT d.* FROM documents d
    JOIN document_access da ON da.document_id = d.id
    WHERE da.user_id = ?
");
$sharedDocs->execute([$userId]);
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Welcome User</h1>

    <h2>Create Document</h2>
    <form method="POST" action="core/handleForms.php">
        <input type="text" name="title" placeholder="Document Title" required>
        <input type="hidden" name="action" value="create_document">
        <button type="submit">Create</button>
    </form>

    <h2>Your Documents</h2>
    <ul>
        <?php while ($doc = $ownedDocs->fetch()): ?>
            <li>
                <a href="document.php?id=<?= $doc['id'] ?>"><?= htmlspecialchars($doc['title']) ?></a> (Owned)
            </li>
        <?php endwhile; ?>
    </ul>

    <h2>Shared With You</h2>
    <ul>
        <?php while ($doc = $sharedDocs->fetch()): ?>
            <li>
                <a href="document.php?id=<?= $doc['id'] ?>"><?= htmlspecialchars($doc['title']) ?></a> (Shared)
            </li>
        <?php endwhile; ?>
    </ul>
</body>
</html>
