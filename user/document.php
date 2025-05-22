<?php
require_once 'core/dbConfig.php';

if (!isset($_SESSION['user_id'])) {
    die("Access denied. Not logged in.");
}

$docId = $_GET['id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$docId) {
    die("Document ID is required.");
}

// Check access (owner or shared)
$accessCheck = $pdo->prepare("
    SELECT * FROM documents 
    WHERE id = ? AND (owner_id = ? OR id IN (
        SELECT document_id FROM document_access WHERE user_id = ?
    ))
");
$accessCheck->execute([$docId, $userId, $userId]);

$document = $accessCheck->fetch(PDO::FETCH_ASSOC);
if (!$document) {
    die("Access denied.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($document['title']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        #docContent {
            border: 1px solid #ccc;
            padding: 10px;
            min-height: 300px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <h2><?= htmlspecialchars($document['title']) ?></h2>

    <!-- Editable Document Area -->
    <div id="docContent" contenteditable="true" oninput="autoSave()"><?= htmlspecialchars($document['content']) ?></div>

    <script>
        let timeout = null;
        function autoSave() {
            const content = document.getElementById("docContent").innerHTML;
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "core/handleForms.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.send("action=autosave&doc_id=<?= $docId ?>&content=" + encodeURIComponent(content));
                console.log("Auto-saving...");
            }, 1000); // Delay save until typing stops for 1 second
        }
    </script>
</body>
</html>
