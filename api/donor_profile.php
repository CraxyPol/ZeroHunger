<?php
$donor_id = (int)($_GET['donor_id'] ?? 0);
if (!$donor_id) jsonResponse(['success'=>false,'error'=>'donor_id required'],400);

$stmt = $conn->prepare("SELECT user_id,name,email,phone_number,profile_photo FROM users WHERE user_id=?");
$stmt->bind_param("i",$donor_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if (!$profile) jsonResponse(['success'=>false,'error'=>'Not found'],404);

$stmt2 = $conn->prepare("
    SELECT d.*, ci.institution_name
    FROM donations d
    LEFT JOIN charitable_institutions ci ON ci.institution_id = d.charity_id
    WHERE d.donor_id=?
    ORDER BY d.created_at DESC
    LIMIT 10
");
$stmt2->bind_param("i",$donor_id);
$stmt2->execute();
$res2 = $stmt2->get_result();

$donations = [];
while($r=$res2->fetch_assoc()) $donations[]=$r;

jsonResponse(['success'=>true,'profile'=>$profile,'donations'=>$donations]);
