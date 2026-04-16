<?php
// ApplesChat — MySQL REST API
// All chat data flows through this single file.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once 'db.php';

$action = $_GET['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

function nowMs()       { return (int)(microtime(true) * 1000); }
function ok($d = [])   { echo json_encode(array_merge(['ok'=>true], $d)); exit; }
function fail($m, $c=400) { http_response_code($c); echo json_encode(['error'=>$m]); exit; }

// Sanitise: only allow expected scalar value, never an array/object from user input
function s($v) { return is_scalar($v) ? $v : null; }

try { switch ($action) {

// ── Users / Presence ──────────────────────────────────────────────────────────

case 'set_user':
    // Called on login
    $stmt = $pdo->prepare(
        "INSERT INTO users (username, avatar, status, last_seen)
         VALUES (?,?,?,?)
         ON DUPLICATE KEY UPDATE avatar=VALUES(avatar), status=VALUES(status), last_seen=VALUES(last_seen)");
    $stmt->execute([s($body['username']), s($body['avatar']), s($body['status']), nowMs()]);
    ok();

case 'ping':
    // Heartbeat — keep user "online" (called every 45 s)
    $stmt = $pdo->prepare("UPDATE users SET last_seen=?, avatar=?, status=? WHERE username=?");
    $stmt->execute([nowMs(), s($body['avatar']), s($body['status']), s($body['username'])]);
    ok();

case 'get_online':
    // Returns users active in the last 3 minutes
    $since = nowMs() - 3 * 60 * 1000;
    $stmt  = $pdo->prepare("SELECT username, avatar, status, last_seen FROM users WHERE last_seen > ?");
    $stmt->execute([$since]);
    echo json_encode($stmt->fetchAll()); exit;

// ── Public Messages ───────────────────────────────────────────────────────────

case 'send_msg':
    $fields = ['username','avatar','status','message','image_data','audio_data','video_data',
               'sticker_url','audio_file_data','audio_file_name','doc_data','doc_name','doc_size',
               'contact_name','contact_phone','contact_email','event_title','event_date','event_time',
               'event_location','poll_id','poll_question','poll_options',
               'reply_to_username','reply_to_text'];
    $cols   = implode(',', $fields) . ',created_at';
    $ph     = implode(',', array_fill(0, count($fields), '?')) . ',?';
    $vals   = array_map(fn($f) => isset($body[$f]) ? s($body[$f]) : null, $fields);
    $ts     = nowMs();
    $vals[] = $ts;
    $stmt   = $pdo->prepare("INSERT INTO messages ($cols) VALUES ($ph)");
    $stmt->execute($vals);
    ok(['id' => (int)$pdo->lastInsertId(), 'created_at' => $ts]);

case 'get_msgs':
    $since = (int)($_GET['since'] ?? 0);
    if ($since === 0) {
        // Initial load: last 100
        $stmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 100");
        echo json_encode(array_reverse($stmt->fetchAll())); exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE created_at > ? ORDER BY created_at ASC LIMIT 50");
    $stmt->execute([$since]);
    echo json_encode($stmt->fetchAll()); exit;

// ── Private Messages ──────────────────────────────────────────────────────────

case 'send_pm':
    $fields = ['chat_id','from_user','to_user','message','image_data','audio_data','video_data',
               'sticker_url','audio_file_data','audio_file_name','doc_data','doc_name','doc_size',
               'contact_name','contact_phone','contact_email','event_title','event_date','event_time',
               'event_location','poll_id','poll_question','poll_options'];
    $cols   = implode(',', $fields) . ',created_at';
    $ph     = implode(',', array_fill(0, count($fields), '?')) . ',?';
    $vals   = array_map(fn($f) => isset($body[$f]) ? s($body[$f]) : null, $fields);
    $ts     = nowMs();
    $vals[] = $ts;
    $stmt   = $pdo->prepare("INSERT INTO private_messages ($cols) VALUES ($ph)");
    $stmt->execute($vals);
    ok(['id' => (int)$pdo->lastInsertId(), 'created_at' => $ts]);

case 'get_pm':
    $chatId = s($_GET['chat_id'] ?? '');
    $since  = (int)($_GET['since'] ?? 0);
    if ($since === 0) {
        $stmt = $pdo->prepare("SELECT * FROM private_messages WHERE chat_id=? ORDER BY created_at DESC LIMIT 80");
        $stmt->execute([$chatId]);
        echo json_encode(array_reverse($stmt->fetchAll())); exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM private_messages WHERE chat_id=? AND created_at>? ORDER BY created_at ASC LIMIT 50");
    $stmt->execute([$chatId, $since]);
    echo json_encode($stmt->fetchAll()); exit;

case 'check_pm':
    // Background unread check: new messages sent TO me since timestamp
    $me    = s($_GET['me']    ?? '');
    $since = (int)($_GET['since'] ?? 0);
    $stmt  = $pdo->prepare(
        "SELECT from_user, COUNT(*) AS cnt
         FROM private_messages
         WHERE to_user=? AND created_at>?
         GROUP BY from_user");
    $stmt->execute([$me, $since]);
    echo json_encode($stmt->fetchAll()); exit;

// ── Polls ─────────────────────────────────────────────────────────────────────

case 'save_poll':
    $stmt = $pdo->prepare(
        "INSERT INTO polls (poll_id, question, options, created_at) VALUES (?,?,?,?)
         ON DUPLICATE KEY UPDATE question=VALUES(question), options=VALUES(options)");
    $stmt->execute([s($body['poll_id']), s($body['question']), s($body['options']), nowMs()]);
    ok();

case 'vote':
    $stmt = $pdo->prepare(
        "INSERT INTO poll_votes (poll_id, username, option_index) VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE option_index=VALUES(option_index)");
    $stmt->execute([s($body['poll_id']), s($body['username']), (int)$body['option_index']]);
    ok();

case 'get_votes':
    $stmt = $pdo->prepare("SELECT username, option_index FROM poll_votes WHERE poll_id=?");
    $stmt->execute([s($_GET['poll_id'] ?? '')]);
    echo json_encode($stmt->fetchAll()); exit;

// ── WebRTC Calls ──────────────────────────────────────────────────────────────

case 'call_init':
    $stmt = $pdo->prepare(
        "INSERT INTO calls (call_id, caller, callee, offer, status, created_at)
         VALUES (?,?,?,?,'ringing',?)
         ON DUPLICATE KEY UPDATE offer=VALUES(offer), status='ringing', created_at=VALUES(created_at)");
    $stmt->execute([s($body['call_id']), s($body['caller']), s($body['callee']), json_encode($body['offer']), nowMs()]);
    ok();

case 'call_answer':
    $stmt = $pdo->prepare("UPDATE calls SET answer=?, status='active' WHERE call_id=?");
    $stmt->execute([json_encode($body['answer']), s($body['call_id'])]);
    ok();

case 'call_status':
    // Set status: 'rejected' or 'ended'
    $stmt = $pdo->prepare("UPDATE calls SET status=? WHERE call_id=?");
    $stmt->execute([s($body['status']), s($body['call_id'])]);
    ok();

case 'call_get':
    $stmt = $pdo->prepare("SELECT * FROM calls WHERE call_id=?");
    $stmt->execute([s($_GET['call_id'] ?? '')]);
    $row = $stmt->fetch();
    if ($row) { $row['offer'] = json_decode($row['offer'],true); $row['answer'] = $row['answer'] ? json_decode($row['answer'],true) : null; }
    echo json_encode($row ?: null); exit;

case 'call_incoming':
    // Callee polls this to detect a ringing call
    $me    = s($_GET['me'] ?? '');
    $since = nowMs() - 30000; // ignore calls older than 30 s
    $stmt  = $pdo->prepare("SELECT * FROM calls WHERE callee=? AND status='ringing' AND created_at>? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$me, $since]);
    $row = $stmt->fetch();
    if ($row) { $row['offer'] = json_decode($row['offer'],true); }
    echo json_encode($row ?: null); exit;

case 'ice_add':
    $stmt = $pdo->prepare("INSERT INTO ice_candidates (call_id, role, candidate, created_at) VALUES (?,?,?,?)");
    $stmt->execute([s($body['call_id']), s($body['role']), json_encode($body['candidate']), nowMs()]);
    ok(['id' => (int)$pdo->lastInsertId()]);

case 'ice_get':
    // Returns ICE candidates for a role after a given id
    $stmt = $pdo->prepare(
        "SELECT id, candidate FROM ice_candidates WHERE call_id=? AND role=? AND id>? ORDER BY id ASC");
    $stmt->execute([s($_GET['call_id']??''), s($_GET['role']??''), (int)($_GET['after']??0)]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) { $r['candidate'] = json_decode($r['candidate'],true); }
    echo json_encode($rows); exit;

// ─────────────────────────────────────────────────────────────────────────────
default:
    fail('Unknown action');

}} catch (Exception $e) { fail($e->getMessage(), 500); }
