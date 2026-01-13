<?php
/**
 * Remotion Studio Authentication API
 * 
 * Authenticates admin users for the Remotion Studio.
 * Uses the same credentials as the main admin panel.
 * 
 * POST /api/remotion/auth.php
 * Body: { "email": string, "password": string }
 * Returns: { "success": bool, "token": string, "user": object }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // For local development
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email and password required']);
    exit;
}

// Find user
$user = Database::fetchOne(
    "SELECT id, email, name, password_hash, role FROM users WHERE email = ? AND role IN ('admin', 'editor')",
    [$email]
);

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
    exit;
}

// Verify password
if (!password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
    exit;
}

// Generate a simple token (in production, use JWT)
$tokenData = [
    'user_id' => $user['id'],
    'email' => $user['email'],
    'exp' => time() + (7 * 24 * 60 * 60), // 7 days
];
$token = base64_encode(json_encode($tokenData) . '.' . hash('sha256', json_encode($tokenData) . getenv('APP_SECRET')));

// Return success
echo json_encode([
    'success' => true,
    'token' => $token,
    'user' => [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'name' => $user['name'],
        'role' => $user['role'],
    ],
]);
