<?php
$donation_id = (int)($_GET['donation_id'] ?? 0);
if (!$donation_id) jsonResponse(['success'=>false,'error'=>'donation_id required'],400);

$stmt = $conn->prepare("
    SELECT d.*, u.name AS donor_name, u.email AS donor_email, u.phone_number AS donor_phone
    FROM donations d
    LEFT JOIN users u ON u.user_id = d.donor_id
    WHERE d.donation_id = ?
");
$stmt->bind_param("i",$donation_id);
$stmt->execute();
$donation = $stmt->get_result()->fetch_assoc();

if (!$donation) jsonResponse(['success'=>false,'error'=>'Donation not found'],404);

// Security: ensure charity owns the donation
if ($me_type !== 'Admin' && $donation['charity_id'] != $charity_institution_id) {
    jsonResponse(['success'=>false,'error'=>'Forbidden'],403);
}

$stmt2 = $conn->prepare("SELECT * FROM donation_items WHERE donation_id = ?");
$stmt2->bind_param("i",$donation_id);
$stmt2->execute();
$res2 = $stmt2->get_result();

$items = [];
while ($r = $res2->fetch_assoc()) $items[] = $r;

jsonResponse(['success'=>true,'donation'=>$donation,'items'=>$items]);
