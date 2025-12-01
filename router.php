<?php
/**
 * router.php — Lightweight API Router
 */

session_start();
require_once __DIR__ . '/../db.php';

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_GET['api'])) {
    echo json_encode(['success' => false, 'error' => 'No API specified']);
    exit;
}

$api = $_GET['api'];

// API file location
$apiFile = __DIR__ . "/api/{$api}.php";

if (!file_exists($apiFile)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Unknown API endpoint']);
    exit;
}

// Include common helpers
require_once __DIR__ . "/api/_helpers.php";

// Run endpoint
require $apiFile;
exit;
