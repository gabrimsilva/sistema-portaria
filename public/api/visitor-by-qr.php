<?php
// Secure API endpoint to find visitor by QR code
require_once '../../config/config.php';

header('Content-Type: application/json');

// Check if user is authenticated and has proper session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Rate limiting (simple implementation)
$maxRequests = 100;
$window = 300; // 5 minutes
$userKey = 'api_rate_' . $_SESSION['user_id'];

if (!isset($_SESSION[$userKey])) {
    $_SESSION[$userKey] = ['count' => 0, 'reset' => time() + $window];
}

if (time() > $_SESSION[$userKey]['reset']) {
    $_SESSION[$userKey] = ['count' => 0, 'reset' => time() + $window];
}

if ($_SESSION[$userKey]['count'] >= $maxRequests) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Rate limit exceeded']);
    exit;
}

$_SESSION[$userKey]['count']++;

$qrCode = $_GET['qr'] ?? '';

if (empty($qrCode)) {
    echo json_encode(['success' => false, 'error' => 'QR code not provided']);
    exit;
}

try {
    $db = new Database();
    $visitor = $db->fetch("SELECT * FROM visitantes WHERE qr_code = ?", [$qrCode]);
    
    if ($visitor) {
        echo json_encode(['success' => true, 'data' => $visitor]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Visitor not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}