<?php
header('Content-Type: application/json');
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$username = trim($data['username'] ?? '');
$status   = trim($data['status']   ?? '');
$avatar   = $data['avatar']  ?? null;

if ($username === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Username is required']);
    exit;
}

// Validate: letters and numbers only, 1-15 chars
if (!preg_match('/^[A-Za-z0-9]{1,15}$/', $username)) {
    http_response_code(400);
    echo json_encode(['error' => 'Username must be 1-15 alphanumeric characters']);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO users (username, status, avatar)
    VALUES (:u, :s, :a)
    ON DUPLICATE KEY UPDATE
        status    = VALUES(status),
        avatar    = VALUES(avatar),
        last_seen = NOW()
");
$stmt->execute([':u' => $username, ':s' => $status, ':a' => $avatar]);

echo json_encode(['success' => true]);
