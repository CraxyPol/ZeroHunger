<?php
// api/activity.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success'=>false,'error'=>'Unauthorized']);
    exit;
}

try {
    // return last 20 activity logs (optionally filter by user)
    $uid = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    if ($uid) {
        $stmt = $conn->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $conn->query("SELECT al.*, u.name FROM activity_logs al LEFT JOIN users u ON u.user_id = al.user_id ORDER BY al.created_at DESC LIMIT 50");
    }
    $out = [];
    while($r = $res->fetch_assoc()) $out[] = $r;
    echo json_encode(['success'=>true,'items'=>$out]);
} catch(Exception $e){
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
