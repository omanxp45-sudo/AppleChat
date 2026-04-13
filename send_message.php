<?php
header('Content-Type: application/json');
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$username   = trim($data['username']    ?? '');
$message    = trim($data['message']     ?? '');
$avatar     = $data['avatar']     ?? null;
$image_data = $data['image_data'] ?? null;
$sticker    = $data['sticker_url'] ?? null;

if ($username === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Username is required']);
    exit;
}

// Must have at least something to send
if ($message === '' && !$image_data && !$sticker) {
    http_response_code(400);
    echo json_encode(['error' => 'Nothing to send']);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO messages (username, avatar, message, image_data, sticker_url)
    VALUES (:u, :av, :m, :img, :st)
");
$stmt->execute([
    ':u'   => $username,
    ':av'  => $avatar   ?: null,
    ':m'   => $message  ?: null,
    ':img' => $image_data ?: null,
    ':st'  => $sticker  ?: null,
]);

echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
