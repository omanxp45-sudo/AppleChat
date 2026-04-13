<?php
header('Content-Type: application/json');
require 'db.php';

$username = trim($_GET['username'] ?? '');

if ($username === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Username is required']);
    exit;
}

$stmt = $pdo->prepare("SELECT username, status, avatar FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user) {
    echo json_encode($user);
} else {
    echo json_encode(['error' => 'User not found']);
}
