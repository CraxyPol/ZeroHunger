<?php
$input = json_decode(file_get_contents("php://input"), true);

$donor_id = (int)($input['donor_id'] ?? 0);
$message  = trim($input['message'] ?? '');

if (!$donor_id || !$message)
    jsonResponse(['success'=>false,'error'=>'Missing fields'],400);

$stmt = $conn->prepare("INSERT INTO messages (sender_id,receiver_id,message) VALUES (?,?,?)");
$stmt->bind_param("iis",$me,$donor_id,$message);
$stmt->execute();

// Notify donor
$note = $conn->prepare("INSERT INTO notifications (user_id,message) VALUES (?,?)");
$msg  = "New message from charity: ".mb_substr($message,0,120);
$note->bind_param("is",$donor_id,$msg);
$note->execute();

jsonResponse(['success'=>true,'message_id'=>$stmt->insert_id]);
