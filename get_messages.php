<?php
header('Content-Type: application/json');
require 'db.php';

// Return only messages newer than this ID (for polling)
$after_id = max(0, intval($_GET['after_id'] ?? 0));

$stmt = $pdo->prepare("
    SELECT id, username, avatar, message, image_data, sticker_url, created_at
    FROM   messages
    WHERE  id > ?
    ORDER  BY id ASC
    LIMIT  100
");
$stmt->execute([$after_id]);
$rows = $stmt->fetchAll();

// Cast id to int so JS can compare numerically
foreach ($rows as &$row) {
    $row['id'] = (int)$row['id'];
}

echo json_encode($rows);
