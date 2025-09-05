<?php
$host = "localhost";
$dbname = "price_comparison";
$username = "root"; // Change if you have a different MySQL username
$password = ""; // Change if your MySQL has a password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
