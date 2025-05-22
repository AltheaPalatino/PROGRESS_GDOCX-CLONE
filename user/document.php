<?php
require_once 'core/dbConfig.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$userId = $_SESSION['user_id'];
$docId = $_GET['id'] ?? null;

if (!$docId) {
    die("No document ID specified.");
}

// Fetch document if the user is the owner or has access
$docQuery = $pdo->prepare("
    SELECT d.*, u.name AS owner_name
    FROM documents d
    JOIN users u ON d.owner_id = u.id
    WHERE d.id = ?
      AND (d.owner_id = ? OR EXISTS (
          SELECT 1 FROM document_access da
          WHERE da.document_id = d.id AND da.user_id = ?
      ))
");
$docQuery->execute([$docId, $userId, $userId]);
$doc = $docQuery->fetch();

if (!$doc) {
    die("You do not have access to this document.");
}

// Log the view
$logView = $pdo->prepare("INSERT INTO activity_logs (document_id, user_id, action) VALUES (?, ?, 'viewed')");
$logView->execute([$docId, $userId]);

// Handle message post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $msg = trim($_POST['message']);
    if (!empty($msg)) {
        $insertMsg = $pdo->prepare("INSERT INTO messages (document_id, user_id, message) VALUES (?, ?, ?)");
        $insertMsg->execute([$docId, $userId, $msg]);
        // Redirect to avoid form resubmission
        header("Location: document.php?id=$docId");
        exit;
    }
}

// Handle content update (autosave or manual save)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $newContent = $_POST['content'];

    // Check if user has edit rights
    $checkEdit = $pdo->prepare("SELECT 1 FROM document_access WHERE document_id = ? AND user_id = ?");
    $checkEdit->execute([$docId, $userId]);
    $hasAccess = $doc['owner_id'] == $userId || $checkEdit->fetch();

    if ($hasAccess) {
        $updateDoc = $pdo->prepare("UPDATE documents SET content = ? WHERE id = ?");
        $updateDoc->execute([$newContent, $docId]);

        // Log the edit
        $logEdit = $pdo->prepare("INSERT INTO activity_logs (document_id, user_id, action) VALUES (?, ?, 'edited')");
        $logEdit->execute([$docId, $userId]);

        // For autosave: no redirect, for manual submit: you can redirect if you want.
        if (!isset($_POST['autosave'])) {
            header("Location: document.php?id=$docId");
            exit;
        }
    }
}

// Handle sharing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_user_id'])) {
    $shareTo = $_POST['share_user_id'];

    // Prevent duplicate access
    $check = $pdo->prepare("SELECT 1 FROM document_access WHERE document_id = ? AND user_id = ?");
    $check->execute([$docId, $shareTo]);
    if (!$check->fetch()) {
        $addAccess = $pdo->prepare("INSERT INTO document_access (document_id, user_id) VALUES (?, ?)");
        $addAccess->execute([$docId, $shareTo]);

        $logShare = $pdo->prepare("INSERT INTO activity_logs (document_id, user_id, action) VALUES (?, ?, 'shared with user $shareTo')");
        $logShare->execute([$docId, $userId]);
    }
    header("Location: document.php?id=$docId");
    exit;
}

// Fetch messages
$msgs = $pdo->prepare("
    SELECT m.*, u.name AS sender 
    FROM messages m 
    JOIN users u ON u.id = m.user_id
    WHERE m.document_id = ?
    ORDER BY m.timestamp DESC
");
$msgs->execute([$docId]);

// Fetch activity logs
$logs = $pdo->prepare("
    SELECT a.*, u.name AS actor 
    FROM activity_logs a 
    JOIN users u ON u.id = a.user_id
    WHERE a.document_id = ?
    ORDER BY a.timestamp DESC
");
$logs->execute([$docId]);

// Fetch user list for sharing
$users = $pdo->query("SELECT id, name FROM users WHERE role = 'user'");

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?= htmlspecialchars($doc['title']) ?> - Document</title>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; max-width: 900px; margin: auto; }
    textarea { width: 100%; height: 200px; font-family: monospace; font-size: 14px; }
    .box { border: 1px solid #ccc; padding: 10px; margin-bottom: 20px; border-radius: 6px; }
    .hidden-section { display: none; max-height: 300px; overflow-y: auto; border: 1px solid #eee; padding: 10px; margin-top: 5px; background: #f9f9f9; }
    .toggle-btn { cursor: pointer; user-select: none; font-size: 24px; }
    .log, .msg { margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
    select, input[type=text], button { font-size: 14px; padding: 5px; }
    #searchInput { width: 100%; padding: 6px; margin-top: 5px; margin-bottom: 5px; }
    pre { background: #eee; padding: 10px; white-space: pre-wrap; word-wrap: break-word; }
</style>
<script>
    function toggleSection(id) {
        const el = document.getElementById(id);
        if (el.style.display === "block") {
            el.style.display = "none";
        } else {
            el.style.display = "block";
        }
    }

    function searchUsers() {
        const input = document.getElementById("searchInput").value.toLowerCase();
        const options = document.getElementById("shareUserSelect").options;
        for (let i = 0; i < options.length; i++) {
            let txt = options[i].text.toLowerCase();
            options[i].style.display = txt.includes(input) ? "" : "none";
        }
    }

    // Autosave content every 5 seconds if content changes
    let timeoutId = null;
    function setupAutosave() {
        const textarea = document.getElementById("contentTextarea");
        let lastValue = textarea.value;
        textarea.addEventListener("input", () => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                if(textarea.value !== lastValue){
                    lastValue = textarea.value;
                    autosaveContent(textarea.value);
                }
            }, 5000);
        });
    }

    function autosaveContent(content) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "document.php?id=<?= $docId ?>", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.send("content=" + encodeURIComponent(content) + "&autosave=1");
    }

    window.onload = function() {
        setupAutosave();
    }
</script>
</head>
<body>

<h1><?= htmlspecialchars($doc['title']) ?></h1>
<p><strong>Owner:</strong> <?= htmlspecialchars($doc['owner_name']) ?></p>

<!-- Hidden toggles for logs, sharing, and messages -->
<div style="display:flex; gap:20px; margin-bottom:20px;">
    <div>
        <span class="toggle-btn" title="Toggle Sharing">📤</span><br>
        <div id="shareSection" class="hidden-section" style="display:none;">
            <?php if ($doc['owner_id'] == $userId): ?>
                <form method="POST" style="margin-bottom:10px;">
                    <input type="text" id="searchInput" oninput="searchUsers()" placeholder="Search users to share...">
                    <select name="share_user_id" id="shareUserSelect" size="5" style="width:200px;">
                        <?php while ($u = $users->fetch()): ?>
                            <?php if ($u['id'] != $userId): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </select><br>
                    <button type="submit" style="margin-top:5px;">Share</button>
                </form>
            <?php else: ?>
                <p>You don't have permission to share this document.</p>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <span class="toggle-btn" title="Toggle Messages">💬</span><br>
        <div id="messagesSection" class="hidden-section" style="display:none;">
            <form method="POST" style="margin-bottom:10px;">
                <textarea name="message" placeholder="Write a message..." required></textarea>
                <button type="submit">Send</button>
            </form>
            <?php while ($m = $msgs->fetch()): ?>
                <div class="msg">
                    <strong><?= htmlspecialchars($m['sender']) ?>:</strong> <?= nl2br(htmlspecialchars($m['message'])) ?>
                    <br><small><?= $m['timestamp'] ?></small>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div>
        <span class="toggle-btn" title="Toggle Activity Logs">📝</span><br>
        <div id="logsSection" class="hidden-section" style="display:none;">
            <?php while ($l = $logs->fetch()): ?>
                <div class="log">
                    <?= htmlspecialchars($l['actor']) ?> <?= htmlspecialchars($l['action']) ?> at <?= $l['timestamp'] ?>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<div class="box">
    <!-- Editable Document Area -->
    <textarea id="contentTextarea" name="content"><?= htmlspecialchars($doc['content']) ?></textarea>
</div>

<script>
    let timeout = null;

    // Attach autosave to textarea
    document.getElementById("contentTextarea").addEventListener("input", autoSave);

    function autoSave() {
        const content = document.getElementById("contentTextarea").value;
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "core/handleForms.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.send("action=autosave&doc_id=<?= $docId ?>&content=" + encodeURIComponent(content));
            console.log("Auto-saving...");
        }, 1000); // Save 1 second after user stops typing
    }
</script>


<script>
    // Connect toggle buttons to sections
    const toggles = document.querySelectorAll(".toggle-btn");
    toggles[0].onclick = () => toggleSection("shareSection");
    toggles[1].onclick = () => toggleSection("messagesSection");
    toggles[2].onclick = () => toggleSection("logsSection");
</script>

</body>
</html>
