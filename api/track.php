<?php
/**
 * Tracking API Endpoint
 * 
 * Receives page view data from the frontend and logs it
 */

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../src/Services/VisitorTracker.php';

    // Check consent
    if (!VisitorTracker::hasConsent()) {
        echo json_encode(['success' => false, 'reason' => 'no_consent']);
        exit;
    }

    // Get request data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    // Get or create visitor
    $visitorId = VisitorTracker::getOrCreateVisitor();

    if (!$visitorId) {
        echo json_encode(['success' => false, 'reason' => 'visitor_creation_failed']);
        exit;
    }

    // Log page view
    $pageUrl = $data['url'] ?? '/';
    $pageType = $data['pageType'] ?? null;
    $templateId = isset($data['templateId']) ? (int) $data['templateId'] : null;

    VisitorTracker::logPageView($visitorId, $pageUrl, $pageType, $templateId);

    echo json_encode([
        'success' => true,
        'visitor_id' => $visitorId
    ]);

} catch (Throwable $e) {
    // Log the error but return clean response
    error_log("track.php ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

    // TEMPORARY DEBUGGING: Always show error details
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
