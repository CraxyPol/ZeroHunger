<?php
// api/user_action.php
// Accepts POST JSON payloads and performs admin actions:
// { action: "verify"|"reject"|"update"|"seed_demo"|"verify_all"|"clear_demo"|"report", email: "...", reason: "...", notes: "...", selfie: "data:image/..." }
// Protect: only Admins
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

// Read JSON body
$body = file_get_contents('php://input');
$payload = json_decode($body, true);
if (!is_array($payload)) $payload = [];

$action = $payload['action'] ?? '';

function respond($data) {
    echo json_encode($data);
    exit;
}

try {
    if ($action === 'verify' || $action === 'reject') {
        $email = $payload['email'] ?? '';
        if (!$email) respond(['success' => false, 'error' => 'Missing email']);

        // Find user
        $s = $conn->prepare("SELECT user_id, account_status FROM users WHERE email = ? OR username = ? LIMIT 1");
        $s->bind_param("ss", $email, $email);
        $s->execute();
        $r = $s->get_result();
        if ($r->num_rows === 0) respond(['success' => false, 'error' => 'User not found']);
        $u = $r->fetch_assoc();
        $user_id = (int)$u['user_id'];

        if ($action === 'verify') {
            // Set account_status => Active (or Verified)
            $upd = $conn->prepare("UPDATE users SET account_status = 'Active' WHERE user_id = ?");
            $upd->bind_param("i", $user_id); $upd->execute();

            // Also update pending_accounts table if present
            $p = $conn->prepare("UPDATE pending_accounts SET admin_action = 'Approved', admin_remarks = NULL WHERE user_id = ?");
            $p->bind_param("i", $user_id); $p->execute();

            respond(['success' => true]);
        } else {
            $reason = trim($payload['reason'] ?? '');
            // Decline the user
            $upd = $conn->prepare("UPDATE users SET account_status = 'Declined', notes = CONCAT(IFNULL(notes,''), ?) WHERE user_id = ?");
            $note = ($reason ? ("[Admin reject] " . $reason . "\n") : "[Admin reject]\n");
            $upd->bind_param("si", $note, $user_id); $upd->execute();

            // update pending_accounts
            $p = $conn->prepare("UPDATE pending_accounts SET admin_action = 'Declined', admin_remarks = ? WHERE user_id = ?");
            $p->bind_param("si", $reason, $user_id); $p->execute();

            respond(['success' => true]);
        }
    }

    if ($action === 'update') {
        $email = $payload['email'] ?? '';
        if (!$email) respond(['success' => false, 'error' => 'Missing email']);

        $s = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR username = ? LIMIT 1");
        $s->bind_param("ss", $email, $email);
        $s->execute();
        $r = $s->get_result();
        if ($r->num_rows === 0) respond(['success' => false, 'error' => 'User not found']);
        $u = $r->fetch_assoc();
        $user_id = (int)$u['user_id'];

        $notes = $payload['notes'] ?? null;
        $selfieData = $payload['selfie'] ?? null; // optional base64 data URL

        if ($selfieData && preg_match('/^data:image\\/(\w+);base64,/', $selfieData, $m)) {
            $type = strtolower($m[1]);
            if (!in_array($type, ['jpg','jpeg','png','webp'])) {
                respond(['success' => false, 'error' => 'Unsupported selfie image type']);
            }
            $base64 = substr($selfieData, strpos($selfieData, ',') + 1);
            $img = base64_decode($base64);
            if ($img === false) respond(['success'=>false,'error'=>'Invalid image data']);

            $uploadsDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
            $filename = time() . '_admin_selfie_' . uniqid() . '.' . $type;
            $path = $uploadsDir . $filename;
            if (file_put_contents($path, $img) === false) {
                respond(['success' => false, 'error' => 'Failed to save selfie']);
            }
            $webPath = 'uploads/' . $filename;

            // Update DB: set selfie = webPath
            $uUpd = $conn->prepare("UPDATE users SET selfie = ? WHERE user_id = ?");
            $uUpd->bind_param("si", $webPath, $user_id);
            $uUpd->execute();
        }

        if (!is_null($notes)) {
            $uUpd = $conn->prepare("UPDATE users SET notes = ? WHERE user_id = ?");
            $uUpd->bind_param("si", $notes, $user_id);
            $uUpd->execute();
        }

        respond(['success' => true]);
    }

    if ($action === 'verify_all') {
        // Set all pending to Active
        $q = "UPDATE users SET account_status = 'Active' WHERE account_status = 'Pending'";
        $conn->query($q);
        // update pending_accounts
        $conn->query("UPDATE pending_accounts SET admin_action = 'Approved' WHERE admin_action != 'Approved'");
        respond(['success' => true]);
    }

    if ($action === 'seed_demo') {
        // Insert a few demo users (safe: generates random usernames/emails)
        $now = date('Y-m-d H:i:s');
        $demoUsers = [
            ['name'=>'Demo Donor','username'=>'demo_donor','email'=>'demo_donor@local','phone'=>'09990001111','password'=>password_hash('password', PASSWORD_DEFAULT),'user_type'=>'Donor','profile_photo'=>NULL,'uploaded_id'=>NULL,'selfie'=>NULL,'account_status'=>'Pending'],
            ['name'=>'Demo Charity','username'=>'demo_charity','email'=>'demo_charity@local','phone'=>'09990002222','password'=>password_hash('password', PASSWORD_DEFAULT),'user_type'=>'CharitableInstitution','profile_photo'=>NULL,'uploaded_id'=>NULL,'selfie'=>NULL,'account_status'=>'Pending'],
        ];

        $ins = $conn->prepare("INSERT INTO users (name, username, email, phone_number, password, user_type, profile_photo, uploaded_id, selfie, account_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($demoUsers as $du) {
            $ins->bind_param("sssssssssss", $du['name'], $du['username'], $du['email'], $du['phone'], $du['password'], $du['user_type'], $du['profile_photo'], $du['uploaded_id'], $du['selfie'], $du['account_status'], $now);
            $ins->execute();
            $newId = $ins->insert_id;
            // Add to pending_accounts
            $p = $conn->prepare("INSERT INTO pending_accounts (user_id) VALUES (?)");
            $p->bind_param("i", $newId);
            $p->execute();
        }
        respond(['success' => true]);
    }

    if ($action === 'clear_demo') {
        // Remove demo users that match demo emails or usernames
        $conn->query("DELETE FROM users WHERE email LIKE 'demo_%' OR username LIKE 'demo_%'");
        // cleanup pending_accounts orphaned
        $conn->query("DELETE pa FROM pending_accounts pa LEFT JOIN users u ON pa.user_id = u.user_id WHERE u.user_id IS NULL");
        respond(['success' => true]);
    }

    if ($action === 'report') {
        $email = $payload['email'] ?? '';
        $message = 'Report created for: ' . $email;
        $ins = $conn->prepare("INSERT INTO admin_reports (message, created_at) VALUES (?, NOW())");
        $ins->bind_param("s", $message);
        $ins->execute();
        respond(['success' => true]);
    }

    respond(['success' => false, 'error' => 'Unknown action']);

} catch (Exception $e) {
    respond(['success' => false, 'error' => $e->getMessage()]);
}
