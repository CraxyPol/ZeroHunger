<?php
// GET incoming donations

$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

if ($me_type === 'Admin' && isset($_GET['charity_id'])) {
    $charity_institution_id = (int)$_GET['charity_id'];
}

if ($charity_institution_id) {
    $stmt = $conn->prepare("
        SELECT d.*, u.name AS donor_name, u.email AS donor_email, u.profile_photo
        FROM donations d
        LEFT JOIN users u ON u.user_id = d.donor_id
        WHERE d.charity_id = ?
        ORDER BY d.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("iii", $charity_institution_id, $limit, $offset);
} else {
    $stmt = $conn->prepare("
        SELECT d.*, u.name AS donor_name, ci.institution_name AS charity_name
        FROM donations d
        LEFT JOIN users u ON u.user_id = d.donor_id
        LEFT JOIN charitable_institutions ci ON ci.institution_id = d.charity_id
        ORDER BY d.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$res = $stmt->get_result();
$out = [];

while ($row = $res->fetch_assoc()) {
    $out[] = $row;
}

jsonResponse(['success' => true, 'donations' => $out]);
