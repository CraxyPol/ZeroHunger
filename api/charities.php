<?php
// api/charities.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success'=>false,'error'=>'Unauthorized']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    if ($id) {
        $stmt = $conn->prepare("SELECT institution_id as id, user_id, institution_name as name, number_of_children, profile_description as about FROM charitable_institutions WHERE institution_id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $c = $res->fetch_assoc();
        if(!$c) echo json_encode(['success'=>false,'error'=>'Not found']);
        else {
            // enrich with demo fields if db doesn't have them (safe)
            $c['img'] = $c['img'] ?? 'images/som.png';
            $c['status'] = 'active';
            $c['rating'] = 4.5;
            $c['focusArea'] = 'General';
            $c['contactName'] = '';
            $c['phone'] = '';
            $c['location'] = '';
            echo json_encode(['success'=>true,'charity'=>$c]);
        }
        exit;
    }

    // list all charities
    $q = "SELECT institution_id as id, institution_name as name, number_of_children, profile_description as about FROM charitable_institutions ORDER BY institution_name ASC";
    $rs = $conn->query($q);
    $out = [];
    while($r = $rs->fetch_assoc()){
        $out[] = [
            'id' => $r['id'],
            'name' => $r['name'],
            'about' => $r['about'],
            'img' => 'images/som.png', // you may store a logo path in your table later
            'status' => 'active',
            'rating' => 4.5,
            'focusArea' => 'Community',
            'contactName' => '',
            'phone' => '',
            'location' => ''
        ];
    }
    echo json_encode(['success'=>true,'charities'=>$out]);
} catch(Exception $e){
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
