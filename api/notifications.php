<?php
$stmt = $conn->prepare("
    SELECT * FROM notifications
    WHERE user_id=?
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->bind_param("i",$me);
$stmt->execute();
$res = $stmt->get_result();

$arr = [];
while($n=$res->fetch_assoc()) $arr[]=$n;

jsonResponse(['success'=>true,'notifications'=>$arr]);
