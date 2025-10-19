<?php
/**
 * GM_V41 - SmartLife AI Organizer
 * API Router
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/EventController.php';

$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($scriptName, '', $requestUri);
$path = strtok($path, '?');
$path = trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'];

function notFound() {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Endpoint non trovato']]);
    exit;
}

try {
    // Health check
    if ($path === 'health' || $path === 'api/health') {
        echo json_encode(['status' => 'ok', 'version' => '1.0.0', 'timestamp' => date('c')]);
        exit;
    }
    
    // Auth
    if ($path === 'auth/register' && $method === 'POST') {
        AuthController::register();
        exit;
    }
    if ($path === 'auth/login' && $method === 'POST') {
        AuthController::login();
        exit;
    }
    
    // Protected routes
    AuthMiddleware::authenticate();
    
    if ($path === 'auth/me' && $method === 'GET') {
        AuthController::me();
        exit;
    }
    
    // Events
    if ($path === 'events') {
        if ($method === 'GET') EventController::index();
        elseif ($method === 'POST') EventController::create();
        else notFound();
        exit;
    }
    
    if (preg_match('/^events\/(\d+)$/', $path, $m)) {
        $id = $m[1];
        if ($method === 'GET') EventController::show($id);
        elseif ($method === 'PUT') EventController::update($id);
        elseif ($method === 'DELETE') EventController::delete($id);
        else notFound();
        exit;
    }
    
    if (preg_match('/^events\/(\d+)\/(complete|uncomplete)$/', $path, $m)) {
        if ($method === 'POST') EventController::toggleStatus($m[1]);
        else notFound();
        exit;
    }
    
    notFound();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => ['code' => 'SERVER_ERROR', 'message' => $e->getMessage()]]);
}
