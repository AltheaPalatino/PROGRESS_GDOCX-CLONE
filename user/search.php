<?php
require_once '../core/dbConfig.php';

$query = $_GET['q'] ?? '';
$docId = $_GET['doc_id'] ?? 0;

$stmt = $pdo->prepare("SELECT id, username FROM users WHERE username LIKE ? AND id != ?");
$stmt->execute(["%$query%", $_SESSION['user_id']]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $user) {
    // Check if already has access
    $check = $pdo->prepare("SELECT * FROM document_access WHERE document_id = ? AND user_id = ?");
    $check->execute([$docId, $user['id']]);
    $hasAccess = $check->rowCount() > 0;

    echo "<div>
            {$user['username']}
            " . ($hasAccess ? "<span>(Has Access)</span>" :
            "<button onclick='addUserToDoc({$user['id']})'>Add</button>") . "
         </div>";
}
?>
