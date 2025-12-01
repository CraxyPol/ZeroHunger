<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'Admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin.php");
    exit;
}

$pending_id = isset($_POST['pending_id']) ? (int)$_POST['pending_id'] : 0;
$action = $_POST['action'] ?? '';
$remarks = $_POST['remarks'] ?? null;

if (!$pending_id || !in_array($action, ['Approved','Declined','Pending'])) {
    $_SESSION['flash_error'] = "Invalid request.";
    header("Location: admin.php");
    exit;
}

// Find pending record and user_id
$stmt = $conn->prepare("SELECT user_id FROM pending_accounts WHERE pending_id = ? LIMIT 1");
$stmt->bind_param("i", $pending_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows !== 1) {
    $_SESSION['flash_error'] = "Pending record not found.";
    header("Location: admin.php");
    exit;
}
$row = $res->fetch_assoc();
$user_id = $row['user_id'];

// update pending_accounts
$u = $conn->prepare("UPDATE pending_accounts SET admin_action = ?, admin_remarks = ? WHERE pending_id = ?");
$u->bind_param("ssi", $action, $remarks, $pending_id);
$u->execute();

// update users.account_status
$newStatus = ($action === 'Approved') ? 'Active' : (($action === 'Declined') ? 'Declined' : 'Pending');
$u2 = $conn->prepare("UPDATE users SET account_status = ? WHERE user_id = ?");
$u2->bind_param("si", $newStatus, $user_id);
$u2->execute();

$_SESSION['flash_success'] = "Action saved.";
header("Location: admin.php");
exit;
