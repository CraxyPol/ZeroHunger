<?php
$donor_id = (int)($_GET['donor_id'] ?? 0);
if (!$donor_id) jsonResponse(['success'=>false,'error'=>'donor_id required'],400);

$stmt = $conn->prepare("
    SELECT m.*, u.name AS sender_name
    FROM messages m
    JOIN users u ON u.user_id = m.sender_id
    WHERE (sender_id=? AND receiver_id=?)
       OR (sender_id=? AND receiver_id=?)
    ORDER BY created_at ASC
");
$stmt->bind_param("iiii",$me,$donor_id,$donor_id,$me);
$stmt->execute();
$res = $stmt->get_result();

$messages = [];
while($m = $res->fetch_assoc()) $messages[] = $m;

jsonResponse(['success'=>true,'messages'=>$messages]);
