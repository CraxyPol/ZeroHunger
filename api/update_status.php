<?php
$input = json_decode(file_get_contents("php://input"), true);

$donation_id = (int)($input['donation_id'] ?? 0);
$status      = $input['status'] ?? '';

$allowed = ['pending','confirmed','in_progress','completed','rejected'];
if (!in_array($status,$allowed)) {
    jsonResponse(['success'=>false,'error'=>'Invalid status'],400);
}

// Verify donation
$stmt = $conn->prepare("SELECT donor_id, charity_id FROM donations WHERE donation_id=? LIMIT 1");
$stmt->bind_param("i",$donation_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) jsonResponse(['success'=>false,'error'=>'Not found'],404);

// Security
if ($me_type !== 'Admin' && $row['charity_id'] != $charity_institution_id) {
    jsonResponse(['success'=>false,'error'=>'Forbidden'],403);
}

// Update
$stmt = $conn->prepare("UPDATE donations SET status=?, updated_at=NOW() WHERE donation_id=?");
$stmt->bind_param("si",$status,$donation_id);
$stmt->execute();

// Notify donor
$donor_id = (int)$row['donor_id'];
$note = $conn->prepare("INSERT INTO notifications (user_id,message) VALUES (?,?)");
$msg  = "Your donation #$donation_id status changed to: $status";
$note->bind_param("is",$donor_id,$msg);
$note->execute();

jsonResponse(['success'=>true]);
