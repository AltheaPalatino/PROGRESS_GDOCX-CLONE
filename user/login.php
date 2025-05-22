<?php
require_once 'core/dbConfig.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $password = $_POST['password'];

    if (empty($name) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE name = ?");
        $stmt->execute([$name]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_suspended']) {
                $error = "Your account is suspended.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                header("Location: index.php");
                exit;
            }
        } else {
            $error = "Invalid login credentials.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Login</title></head>
<body>
<h2>Login</h2>
<form method="POST">
    <label>Username:</label><br>
    <input type="text" name="name" required><br><br>
    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>
    <button type="submit">Login</button>
</form>
<?php
if (!empty($_GET['registered'])) echo "<p style='color:green;'>Account created. Please login.</p>";
if (!empty($error)) echo "<p style='color:red;'>$error</p>";
?>
<p>Need an account? <a href="register.php">Register here</a>.</p>
</body>
</html>
