<?php
// api/messages.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success'=>false,'error'=>'Unauthorized']);
    exit;
}

$me = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $_REQUEST['action'] ?? ($input['action'] ?? 'fetch');

try {
    if ($action === 'send') {
        $receiver = (int)($input['receiver_id'] ?? 0);
        $message = trim($input['message'] ?? '');
        if (!$receiver || !$message) { echo json_encode(['success'=>false,'error'=>'Missing fields']); exit; }

        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $me, $receiver, $message);
        $stmt->execute();

        // activity log
        $log = $conn->prepare("INSERT INTO activity_logs (user_id, type, description) VALUES (?, 'message_sent', ?)");
        $desc = "User $me sent message to $receiver";
        $log->bind_param("is", $me, $desc);
        $log->execute();

        echo json_encode(['success'=>true, 'message_id' => $stmt->insert_id]);
        exit;
    }

    if ($action === 'fetch') {
        $other = isset($_GET['other_id']) ? (int)$_GET['other_id'] : (int)($input['other_id'] ?? 0);
        if (!$other) { echo json_encode(['success'=>false,'error'=>'Missing other_id']); exit; }

        $stmt = $conn->prepare("SELECT m.*, u.name as sender_name FROM messages m JOIN users u ON u.user_id = m.sender_id WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY m.created_at ASC");
        $stmt->bind_param("iiii", $me, $other, $other, $me);
        $stmt->execute();
        $res = $stmt->get_result();
        $msgs = [];
        while($r = $res->fetch_assoc()) $msgs[] = $r;

        echo json_encode(['success'=>true,'messages'=>$msgs]);
        exit;
    }

    echo json_encode(['success'=>false,'error'=>'Unknown action']);
} catch(Exception $e){
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
