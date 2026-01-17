<?php
/**
 * Remotion Studio Authentication API
 * 
 * Authenticates admin users for the Remotion Studio.
 * Supports two authentication methods:
 * 1. API Key (recommended for render worker)
 * 2. Email/Password (for users with password set)
 * 
 * POST /api/remotion/auth.php
 * Body: { "api_key": string } OR { "email": string, "password": string }
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

// ============================================
// METHOD 1: API Key Authentication (Preferred)
// ============================================
$apiKey = $input['api_key'] ?? '';
if (!empty($apiKey)) {
    $expectedApiKey = getenv('REMOTION_API_KEY');
    
    if (empty($expectedApiKey)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'API key not configured on server']);
        exit;
    }
    
    if (!hash_equals($expectedApiKey, $apiKey)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid API key']);
        exit;
    }
    
    // API key is valid - find any admin user for the token
    $user = Database::fetchOne(
        "SELECT id, email, name, role FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1"
    );
    
    if (!$user) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'No admin user found']);
        exit;
    }
    
    // Generate token for API key auth
    $tokenData = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'auth_method' => 'api_key',
        'exp' => time() + (30 * 24 * 60 * 60), // 30 days for API key
    ];
    $appSecret = getenv('APP_SECRET') ?: 'default-secret-change-me';
    $token = base64_encode(json_encode($tokenData) . '.' . hash('sha256', json_encode($tokenData) . $appSecret));
    
    echo json_encode([
        'success' => true,
        'token' => $token,
        'auth_method' => 'api_key',
        'user' => [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
        ],
    ]);
    exit;
}

// ============================================
// METHOD 2: Email/Password Authentication
// ============================================
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'API key OR email/password required']);
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
    'auth_method' => 'password',
    'exp' => time() + (7 * 24 * 60 * 60), // 7 days
];
$appSecret = getenv('APP_SECRET') ?: 'default-secret-change-me';
$token = base64_encode(json_encode($tokenData) . '.' . hash('sha256', json_encode($tokenData) . $appSecret));

// Return success
echo json_encode([
    'success' => true,
    'token' => $token,
    'auth_method' => 'password',
    'user' => [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'name' => $user['name'],
        'role' => $user['role'],
    ],
]);
