<?php

$host = "sql108.infinityfree.com";
$dbname = "if0_39095183_church_site";
$username = "if0_39095183";
$password = "OWP5FrCEUIJIy1";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Database connected successfully!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

?>