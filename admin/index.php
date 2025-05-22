<?php
require_once 'core/dbConfig.php';

// Simulate admin login
$_SESSION['user_id'] = 1; // admin1
$_SESSION['role'] = 'admin';

// Get all users
$users = $pdo->query("SELECT * FROM users WHERE role = 'user'")->fetchAll();

// Get all documents
$documents = $pdo->query("
    SELECT d.*, u.username AS owner FROM documents d
    JOIN users u ON d.owner_id = u.id
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <script>
    function toggleSuspend(userId, checkbox) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "core/handleForms.php");
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.send("action=toggle_suspend&user_id=" + userId + "&status=" + (checkbox.checked ? 1 : 0));
    }
    </script>
</head>
<body>
    <h1>Admin Dashboard</h1>

    <h2>User Accounts</h2>
    <table border="1">
        <tr><th>Username</th><th>Suspended?</th></tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td>
                    <input type="checkbox" <?= $user['is_suspended'] ? 'checked' : '' ?>
                           onchange="toggleSuspend(<?= $user['id'] ?>, this)">
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>All Documents</h2>
    <ul>
        <?php foreach ($documents as $doc): ?>
            <li>
                <?= htmlspecialchars($doc['title']) ?> by <?= htmlspecialchars($doc['owner']) ?>
                - <a href="document.php?id=<?= $doc['id'] ?>">View</a>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
