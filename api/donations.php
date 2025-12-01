<?php
// api/donations.php
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
$action = $_REQUEST['action'] ?? ($input['action'] ?? 'list');

try {
    if ($action === 'create') {
        // create donation
        $charity_id = !empty($input['charity_id']) ? (int)$input['charity_id'] : null;
        $details = trim($input['details'] ?? '');
        $pickup_address = trim($input['pickup_address'] ?? '');
        $pickup_time = trim($input['pickup_time'] ?? '');

        if (!$details || !$pickup_address || !$pickup_time) {
            echo json_encode(['success'=>false,'error'=>'Missing fields']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO donations (donor_id, charity_id, details, pickup_address, pickup_time, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iisss", $me, $charity_id, $details, $pickup_address, $pickup_time);
        $stmt->execute();

        $donation_id = $stmt->insert_id;
        // Log activity
        $log = $conn->prepare("INSERT INTO activity_logs (user_id, type, description) VALUES (?, 'donation_created', ?)");
        $desc = "Donation request #$donation_id created by user $me";
        $log->bind_param("is", $me, $desc);
        $log->execute();

        echo json_encode(['success'=>true,'donation_id'=>$donation_id]);
        exit;
    }

    if ($action === 'stats') {
        // simple stats for this donor
        $o = ['total_donations' => 0, 'active_requests' => 0, 'people_helped' => 0, 'impact_score' => 0];
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM donations WHERE donor_id = ?");
        $stmt->bind_param("i",$me); $stmt->execute(); $r = $stmt->get_result()->fetch_assoc(); $o['total_donations'] = (int)$r['cnt'];
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM donations WHERE donor_id = ? AND status IN ('pending','approved','in_progress')");
        $stmt->bind_param("i",$me); $stmt->execute(); $r = $stmt->get_result()->fetch_assoc(); $o['active_requests'] = (int)$r['cnt'];
        // placeholder values (you can compute more meaningful metrics)
        $o['people_helped'] = $o['total_donations'] * 5;
        $o['impact_score'] = min(100, $o['total_donations'] * 5);
        echo json_encode(['success'=>true,'stats'=>$o]);
        exit;
    }

    // default: list donations for current user
    $stmt = $conn->prepare("SELECT d.*, ci.institution_name FROM donations d LEFT JOIN charitable_institutions ci ON d.charity_id = ci.institution_id WHERE d.donor_id = ? ORDER BY d.created_at DESC");
    $stmt->bind_param("i", $me);
    $stmt->execute();
    $res = $stmt->get_result();
    $list = [];
    while($r = $res->fetch_assoc()){
        $list[] = $r;
    }
    echo json_encode(['success'=>true,'donations'=>$list]);

} catch(Exception $e){
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
