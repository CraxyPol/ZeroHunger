<?php
// api/users.php
// Returns JSON: { success: true, users: [...], reports: [...] }
// Protect: only Admins

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../db.php'; // adjust path if your structure differs

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

try {
    // Fetch users
    $q = "SELECT user_id, name, username, email, phone_number, user_type, profile_photo, uploaded_id, selfie, id_image, account_status, created_at, notes FROM users ORDER BY created_at DESC";
    $stmt = $conn->prepare($q);
    $stmt->execute();
    $res = $stmt->get_result();

    $users = [];
    while ($row = $res->fetch_assoc()) {
        // Normalise fields for frontend convenience
        $user = [
            'user_id' => (int)$row['user_id'],
            'name' => $row['name'],
            'username' => $row['username'],
            'email' => $row['email'],
            'phone_number' => $row['phone_number'],
            'user_type' => $row['user_type'],
            'profile_photo' => $row['profile_photo'],   // may be path like "uploads/..."
            'uploaded_id' => $row['uploaded_id'],       // legacy: varchar path
            'selfie' => $row['selfie'],                 // may be path (uploads/...) or longtext base64 depending on previous code
            'id_image' => $row['id_image'],             // longtext if used
            'account_status' => $row['account_status'],
            'notes' => $row['notes'],
            'created_at' => $row['created_at'],
        ];

        // If id_image is empty but uploaded_id contains a path, map it for UI to use
        if (empty($user['id_image']) && !empty($user['uploaded_id'])) {
            $user['id_image'] = $user['uploaded_id'];
        }

        // If selfie is a longtext base64 (starts with "data:image/"), leave as-is for frontend to render.
        // Otherwise the frontend will treat selfie as a relative path e.g. "uploads/...".
        $users[] = $user;
    }

    // Fetch a few recent reports
    $rQ = "SELECT id, message, created_at FROM admin_reports ORDER BY created_at DESC LIMIT 20";
    $rs = $conn->query($rQ);
    $reports = [];
    if ($rs) {
        while ($r = $rs->fetch_assoc()) {
            $reports[] = ['id' => (int)$r['id'], 'text' => $r['message'], 'created' => $r['created_at']];
        }
    }

    echo json_encode(['success' => true, 'users' => $users, 'reports' => $reports]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
