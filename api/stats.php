<?php
$out = [
    'incoming_total' => 0,
    'incoming_pending' => 0,
    'recent_donations' => []
];

if ($charity_institution_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM donations WHERE charity_id=?");
    $stmt->bind_param("i",$charity_institution_id);
    $stmt->execute();
    $out['incoming_total'] = (int)$stmt->get_result()->fetch_assoc()['cnt'];

    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM donations WHERE charity_id=? AND status='pending'");
    $stmt->bind_param("i",$charity_institution_id);
    $stmt->execute();
    $out['incoming_pending'] = (int)$stmt->get_result()->fetch_assoc()['cnt'];

    $stmt = $conn->prepare("
        SELECT d.*, u.name AS donor_name
        FROM donations d
        LEFT JOIN users u ON u.user_id=d.donor_id
        WHERE d.charity_id=?
        ORDER BY d.created_at DESC
        LIMIT 6
    ");
    $stmt->bind_param("i",$charity_institution_id);
} else {
    // admin
    $stmt = $conn->prepare("
        SELECT d.*, u.name AS donor_name
        FROM donations d
        LEFT JOIN users u ON u.user_id=d.donor_id
        ORDER BY d.created_at DESC
        LIMIT 6
    ");
}

$stmt->execute();
$res = $stmt->get_result();
while($r=$res->fetch_assoc()) $out['recent_donations'][]=$r;

jsonResponse(['success'=>true,'stats'=>$out]);
