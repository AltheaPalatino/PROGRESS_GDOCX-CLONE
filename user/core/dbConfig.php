<?php 
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "finals_gdocx";
$dsn = "mysql:host={$host};dbname={$dbname}";

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '+08:00';");
} catch (PDOException $e) {
    die("DB Connection Failed: " . $e->getMessage());
}
?>
